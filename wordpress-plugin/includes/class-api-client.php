<?php
/**
 * Talks to the Postbag REST API.
 *
 * @package PostbagSignup
 */

namespace Postbag_Signup;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrapper around the WordPress HTTP API. The token stays on the server: browsers talk to
 * this site's REST route, never to Postbag directly.
 */
class Api_Client {

	/**
	 * Base URL of the Postbag app, without a trailing slash.
	 *
	 * @var string
	 */
	private $base_url;

	/**
	 * API token with the "write" (and, for the connection test, "read") ability.
	 *
	 * @var string
	 */
	private $token;

	/**
	 * Constructor.
	 *
	 * @param string $base_url Postbag URL.
	 * @param string $token    API token.
	 */
	public function __construct( string $base_url, string $token ) {
		$this->base_url = untrailingslashit( $base_url );
		$this->token    = $token;
	}

	/**
	 * Builds a client from the saved settings.
	 *
	 * @param Settings $settings Plugin settings.
	 */
	public static function from_settings( Settings $settings ): self {
		return new self( $settings->get( 'api_url' ), $settings->get( 'api_token' ) );
	}

	/**
	 * Whether a URL and token are configured.
	 */
	public function is_configured(): bool {
		return '' !== $this->base_url && '' !== $this->token;
	}

	/**
	 * Adds an email address to a list.
	 *
	 * @param string $list_id List UUID.
	 * @param string $email   Email address.
	 * @param string $name    Optional name.
	 * @return array<string, mixed>|WP_Error Decoded response, or an error with a message safe to show visitors.
	 */
	public function subscribe( string $list_id, string $email, string $name = '' ) {
		return $this->request(
			'POST',
			'/api/v1/lists/' . rawurlencode( $list_id ) . '/subscribers',
			array_filter(
				array(
					'email'  => $email,
					'name'   => $name,
					'source' => 'wordpress',
				)
			)
		);
	}

	/**
	 * Lists the mailing lists the token can see (used to test the connection).
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public function lists() {
		return $this->request( 'GET', '/api/v1/lists' );
	}

	/**
	 * Sends a request and turns the response into data or a WP_Error.
	 *
	 * @param string               $method HTTP method.
	 * @param string               $path   API path.
	 * @param array<string, mixed> $body   JSON body.
	 * @return array<string, mixed>|WP_Error
	 */
	private function request( string $method, string $path, array $body = array() ) {
		if ( ! $this->is_configured() ) {
			return new WP_Error( 'postbag_not_configured', __( 'The newsletter signup is not set up yet.', 'postbag-signup' ) );
		}

		$args = array(
			'method'  => $method,
			'timeout' => 10,
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->token,
				'Accept'        => 'application/json',
				'Content-Type'  => 'application/json',
				'User-Agent'    => 'PostbagSignup/' . POSTBAG_SIGNUP_VERSION . '; ' . home_url(),
			),
		);
		if ( array() !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( $this->base_url . $path, $args );

		return self::parse_response( $response );
	}

	/**
	 * Maps a wp_remote_request() result to data or an error.
	 *
	 * @param array<string, mixed>|WP_Error $response Raw response.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function parse_response( $response ) {
		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'postbag_unreachable', __( 'The newsletter service could not be reached. Please try again later.', 'postbag-signup' ), $response->get_error_message() );
		}

		$status = (int) wp_remote_retrieve_response_code( $response );
		$data   = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		$data   = is_array( $data ) ? $data : array();

		if ( $status >= 200 && $status < 300 ) {
			return $data;
		}
		if ( 422 === $status ) {
			$message = isset( $data['errors']['email'][0] ) ? (string) $data['errors']['email'][0] : (string) ( $data['message'] ?? '' );
			return new WP_Error( 'postbag_invalid', '' !== $message ? $message : __( 'Please check your email address.', 'postbag-signup' ) );
		}
		if ( 429 === $status ) {
			return new WP_Error( 'postbag_busy', __( 'Too many signups right now. Please try again in a minute.', 'postbag-signup' ) );
		}
		if ( 401 === $status || 403 === $status ) {
			return new WP_Error( 'postbag_auth', __( 'The newsletter signup is misconfigured (API token).', 'postbag-signup' ), $status );
		}
		if ( 404 === $status ) {
			return new WP_Error( 'postbag_not_found', __( 'This newsletter list no longer exists.', 'postbag-signup' ), $status );
		}

		return new WP_Error( 'postbag_error', __( 'Something went wrong. Please try again later.', 'postbag-signup' ), $status );
	}
}

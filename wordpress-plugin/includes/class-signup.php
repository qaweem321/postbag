<?php
/**
 * The signup itself: validation, spam checks, then the API call.
 *
 * @package PostbagSignup
 */

namespace Postbag_Signup;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Used by the REST route and WP-CLI, so both follow the same rules.
 */
class Signup {

	/** Signups allowed per visitor IP in RATE_WINDOW seconds. */
	const RATE_LIMIT  = 5;
	const RATE_WINDOW = 10 * MINUTE_IN_SECONDS;

	/** Bots fill forms instantly; people take at least this long. */
	const MIN_SECONDS = 2;

	/**
	 * API client.
	 *
	 * @var Api_Client
	 */
	private $client;

	/**
	 * Settings.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Constructor.
	 *
	 * @param Api_Client $client   API client.
	 * @param Settings   $settings Settings.
	 */
	public function __construct( Api_Client $client, Settings $settings ) {
		$this->client   = $client;
		$this->settings = $settings;
	}

	/**
	 * Subscribes a visitor.
	 *
	 * @param array<string, mixed> $input Form fields: email, name, list, website (honeypot), started (timestamp).
	 * @param string               $ip    Visitor IP, for rate limiting.
	 * @return string|WP_Error Message to show the visitor, or an error.
	 */
	public function handle( array $input, string $ip ) {
		// Honeypot and timing: answer like a success, so bots learn nothing.
		$started = isset( $input['started'] ) ? (int) $input['started'] : 0;
		if ( ! empty( $input['website'] ) || ( $started > 0 && time() - $started < self::MIN_SECONDS ) ) {
			return __( 'Thanks! Check your inbox to confirm your subscription.', 'postbag-signup' );
		}

		$email = sanitize_email( (string) ( $input['email'] ?? '' ) );
		if ( '' === $email || ! is_email( $email ) ) {
			return new WP_Error( 'postbag_invalid_email', __( 'Please enter a valid email address.', 'postbag-signup' ), array( 'status' => 422 ) );
		}
		$name    = sanitize_text_field( (string) ( $input['name'] ?? '' ) );
		$list_id = Settings::sanitize_uuid( $input['list'] ?? '' );
		$list_id = '' !== $list_id ? $list_id : $this->settings->get( 'list_id' );
		if ( '' === $list_id ) {
			return new WP_Error( 'postbag_no_list', __( 'The newsletter signup is not set up yet.', 'postbag-signup' ), array( 'status' => 503 ) );
		}

		if ( ! $this->within_rate_limit( $ip ) ) {
			return new WP_Error( 'postbag_rate_limited', __( 'Too many attempts. Please try again in a few minutes.', 'postbag-signup' ), array( 'status' => 429 ) );
		}

		$result = $this->client->subscribe( $list_id, $email, $name );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$outcome = (string) ( $result['outcome'] ?? '' );
		if ( 'pending_confirmation' === $outcome ) {
			return __( 'Thanks! Check your inbox to confirm your subscription.', 'postbag-signup' );
		}
		if ( 'already_subscribed' === $outcome ) {
			return __( 'You are already subscribed. Thanks!', 'postbag-signup' );
		}

		return __( 'Thanks for subscribing!', 'postbag-signup' );
	}

	/**
	 * Counts attempts per IP in a transient. The IP is hashed, so no address is stored.
	 *
	 * @param string $ip Visitor IP.
	 */
	private function within_rate_limit( string $ip ): bool {
		$key   = 'postbag_rl_' . md5( wp_salt() . $ip );
		$count = (int) get_transient( $key );
		if ( $count >= self::RATE_LIMIT ) {
			return false;
		}
		set_transient( $key, $count + 1, self::RATE_WINDOW );

		return true;
	}
}

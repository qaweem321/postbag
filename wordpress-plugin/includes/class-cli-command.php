<?php
/**
 * WP-CLI: wp postbag …
 *
 * @package PostbagSignup
 */

namespace Postbag_Signup;

use WP_CLI;

defined( 'ABSPATH' ) || exit;

/**
 * Manage the Postbag connection from the command line.
 */
class Cli_Command {

	/**
	 * Signup handler.
	 *
	 * @var Signup
	 */
	private $signup;

	/**
	 * API client.
	 *
	 * @var Api_Client
	 */
	private $client;

	/**
	 * Constructor.
	 *
	 * @param  Signup     $signup  Signup handler.
	 * @param  Api_Client $client  API client.
	 */
	public function __construct( Signup $signup, Api_Client $client ) {
		$this->signup = $signup;
		$this->client = $client;
	}

	/**
	 * Checks the connection and lists the available lists.
	 *
	 * ## EXAMPLES
	 *
	 *     wp postbag status
	 *
	 * @when after_wp_load
	 */
	public function status(): void {
		$lists = $this->client->lists();
		if ( is_wp_error( $lists ) ) {
			WP_CLI::error( $lists->get_error_message() );
		}
		$rows = array_map(
			static fn ( $item ) => array(
				'id'            => $item['id'],
				'name'          => $item['name'],
				'subscribed'    => $item['subscribed_count'] ?? '',
				'double_opt_in' => ! empty( $item['double_opt_in'] ) ? 'yes' : 'no',
			),
			$lists['data'] ?? array()
		);
		WP_CLI\Utils\format_items( 'table', $rows, array( 'id', 'name', 'subscribed', 'double_opt_in' ) );
		WP_CLI::success( 'Connected to Postbag.' );
	}

	/**
	 * Subscribes an email address.
	 *
	 * ## OPTIONS
	 *
	 * <email>
	 * : The address to subscribe.
	 *
	 * [--name=<name>]
	 * : Optional name.
	 *
	 * [--list=<id>]
	 * : List ID. Defaults to the list chosen in Settings → Postbag.
	 *
	 * ## EXAMPLES
	 *
	 *     wp postbag subscribe jane@example.com --name=Jane
	 *
	 * @param  array<int, string>    $args  Positional arguments.
	 * @param  array<string, string> $assoc_args  Options.
	 */
	public function subscribe( array $args, array $assoc_args ): void {
		$result = $this->signup->handle(
			array(
				'email' => $args[0],
				'name'  => $assoc_args['name'] ?? '',
				'list'  => $assoc_args['list'] ?? '',
			),
			'cli'
		);
		if ( is_wp_error( $result ) ) {
			WP_CLI::error( $result->get_error_message() );
		}
		WP_CLI::success( $result );
	}
}

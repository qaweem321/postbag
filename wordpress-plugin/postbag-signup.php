<?php
/**
 * Plugin Name:       Postbag Signup
 * Plugin URI:        https://github.com/qaweem321/postbag
 * Description:       Newsletter signup forms (block and shortcode) that add subscribers to a Postbag list.
 * Version:           1.0.0
 * Requires at least: 6.6
 * Requires PHP:      8.1
 * Author:            Qaweem Ahmad
 * License:           MIT
 * Text Domain:       postbag-signup
 *
 * @package PostbagSignup
 */

defined( 'ABSPATH' ) || exit;

define( 'POSTBAG_SIGNUP_VERSION', '1.0.0' );
define( 'POSTBAG_SIGNUP_FILE', __FILE__ );
define( 'POSTBAG_SIGNUP_DIR', plugin_dir_path( __FILE__ ) );

require_once POSTBAG_SIGNUP_DIR . 'includes/class-api-client.php';
require_once POSTBAG_SIGNUP_DIR . 'includes/class-settings.php';
require_once POSTBAG_SIGNUP_DIR . 'includes/class-signup.php';
require_once POSTBAG_SIGNUP_DIR . 'includes/class-form.php';
require_once POSTBAG_SIGNUP_DIR . 'includes/class-rest-controller.php';

add_action(
	'plugins_loaded',
	static function () {
		$settings = new Postbag_Signup\Settings();
		$signup   = new Postbag_Signup\Signup( Postbag_Signup\Api_Client::from_settings( $settings ), $settings );
		$form     = new Postbag_Signup\Form( $settings );

		$settings->register_hooks();
		$form->register_hooks();
		( new Postbag_Signup\Rest_Controller( $signup ) )->register_hooks();

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once POSTBAG_SIGNUP_DIR . 'includes/class-cli-command.php';
			WP_CLI::add_command( 'postbag', new Postbag_Signup\Cli_Command( $signup, Postbag_Signup\Api_Client::from_settings( $settings ) ) );
		}
	}
);

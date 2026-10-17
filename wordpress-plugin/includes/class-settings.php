<?php
/**
 * Settings page: Settings → Postbag.
 *
 * @package PostbagSignup
 */

namespace Postbag_Signup;

defined( 'ABSPATH' ) || exit;

/**
 * Stores the Postbag URL, API token and default list, using the Settings API.
 */
class Settings {

	const OPTION = 'postbag_signup_settings';

	const PAGE = 'postbag-signup';

	/**
	 * Defaults for every setting.
	 *
	 * @var array<string, string>
	 */
	private const DEFAULTS = array(
		'api_url'   => '',
		'api_token' => '',
		'list_id'   => '',
	);

	/**
	 * Hooks into the admin.
	 */
	public function register_hooks(): void {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
	}

	/**
	 * Reads one setting.
	 *
	 * @param  string $key  Setting name.
	 */
	public function get( string $key ): string {
		$options = get_option( self::OPTION, array() );
		$options = is_array( $options ) ? $options : array();

		return (string) ( $options[ $key ] ?? self::DEFAULTS[ $key ] ?? '' );
	}

	/**
	 * Registers the option with its sanitizer.
	 */
	public function register_settings(): void {
		register_setting(
			self::PAGE,
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::DEFAULTS,
			)
		);
	}

	/**
	 * Cleans submitted settings. An empty token field keeps the saved token, so it never has to be
	 * printed back into the page.
	 *
	 * @param  mixed $input  Submitted values.
	 * @return array<string, string>
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$token = isset( $input['api_token'] ) ? trim( sanitize_text_field( wp_unslash( $input['api_token'] ) ) ) : '';

		return array(
			'api_url'   => isset( $input['api_url'] ) ? esc_url_raw( trim( wp_unslash( $input['api_url'] ) ), array( 'http', 'https' ) ) : '',
			'api_token' => '' !== $token ? $token : $this->get( 'api_token' ),
			'list_id'   => isset( $input['list_id'] ) ? self::sanitize_uuid( wp_unslash( $input['list_id'] ) ) : '',
		);
	}

	/**
	 * Keeps only a valid UUID.
	 *
	 * @param  mixed $value  Raw value.
	 */
	public static function sanitize_uuid( $value ): string {
		$value = strtolower( trim( (string) $value ) );

		return preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $value ) === 1 ? $value : '';
	}

	/**
	 * Adds Settings → Postbag.
	 */
	public function add_page(): void {
		add_options_page(
			__( 'Postbag newsletter', 'postbag-signup' ),
			__( 'Postbag', 'postbag-signup' ),
			'manage_options',
			self::PAGE,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders the settings page, with a live connection check when settings exist.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$client = Api_Client::from_settings( $this );
		$lists  = $client->is_configured() ? $client->lists() : null;
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Postbag newsletter', 'postbag-signup' ); ?></h1>
			<?php if ( is_wp_error( $lists ) ) { ?>
				<div class="notice notice-error"><p><?php echo esc_html( $lists->get_error_message() ); ?></p></div>
			<?php } elseif ( is_array( $lists ) ) { ?>
				<div class="notice notice-success"><p><?php esc_html_e( 'Connected to Postbag.', 'postbag-signup' ); ?></p></div>
			<?php } ?>
			<form method="post" action="options.php">
				<?php settings_fields( self::PAGE ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="postbag-api-url"><?php esc_html_e( 'Postbag URL', 'postbag-signup' ); ?></label></th>
						<td><input type="url" class="regular-text" id="postbag-api-url" name="<?php echo esc_attr( self::OPTION ); ?>[api_url]" value="<?php echo esc_attr( $this->get( 'api_url' ) ); ?>" placeholder="https://newsletter.example.com" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="postbag-api-token"><?php esc_html_e( 'API token', 'postbag-signup' ); ?></label></th>
						<td>
							<input type="password" class="regular-text" id="postbag-api-token" name="<?php echo esc_attr( self::OPTION ); ?>[api_token]" value="" autocomplete="off" placeholder="<?php echo esc_attr( $this->get( 'api_token' ) !== '' ? __( 'Saved (leave empty to keep it)', 'postbag-signup' ) : '' ); ?>">
							<p class="description"><?php esc_html_e( 'Create one in Postbag under API tokens, with the read and write permissions.', 'postbag-signup' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="postbag-list"><?php esc_html_e( 'Default list', 'postbag-signup' ); ?></label></th>
						<td>
							<?php if ( is_array( $lists ) && ! empty( $lists['data'] ) ) { ?>
								<select id="postbag-list" name="<?php echo esc_attr( self::OPTION ); ?>[list_id]">
									<?php foreach ( $lists['data'] as $list ) { ?>
										<option value="<?php echo esc_attr( $list['id'] ); ?>" <?php selected( $this->get( 'list_id' ), $list['id'] ); ?>><?php echo esc_html( $list['name'] ); ?></option>
									<?php } ?>
								</select>
							<?php } else { ?>
								<input type="text" class="regular-text code" id="postbag-list" name="<?php echo esc_attr( self::OPTION ); ?>[list_id]" value="<?php echo esc_attr( $this->get( 'list_id' ) ); ?>" placeholder="List ID">
								<p class="description"><?php esc_html_e( 'Save the URL and token first to pick a list.', 'postbag-signup' ); ?></p>
							<?php } ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<p><?php echo wp_kses_post( __( 'Add the form with the <strong>Newsletter signup</strong> block, or the <code>[postbag_signup]</code> shortcode.', 'postbag-signup' ) ); ?></p>
		</div>
		<?php
	}
}

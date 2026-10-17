<?php
/**
 * The signup form: shortcode and block.
 */

namespace Postbag_Signup;

defined('ABSPATH') || exit;

/**
 * Renders the form. The block and the shortcode share this markup.
 */
class Form
{
    /**
     * Settings.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param  Settings  $settings  Settings.
     */
    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Registers the shortcode, the block and the front-end assets.
     */
    public function register_hooks(): void
    {
        add_shortcode('postbag_signup', [$this, 'shortcode']);
        add_action('init', [$this, 'register_block_and_assets']);
    }

    /**
     * Registers the block (from block.json) and the script and style the form uses.
     */
    public function register_block_and_assets(): void
    {
        wp_register_script('postbag-signup', plugins_url('assets/signup.js', POSTBAG_SIGNUP_FILE), [], POSTBAG_SIGNUP_VERSION, ['strategy' => 'defer']);
        wp_register_style('postbag-signup', plugins_url('assets/signup.css', POSTBAG_SIGNUP_FILE), [], POSTBAG_SIGNUP_VERSION);
        register_block_type(POSTBAG_SIGNUP_DIR.'blocks/signup');
    }

    /**
     * [postbag_signup list="…" title="…" button="…" show_name="yes"]
     *
     * @param  array<string, string>|string  $atts  Shortcode attributes.
     */
    public function shortcode($atts): string
    {
        $atts = shortcode_atts(
            [
                'list' => '',
                'title' => __('Subscribe to our newsletter', 'postbag-signup'),
                'button' => __('Subscribe', 'postbag-signup'),
                'show_name' => 'no',
            ],
            is_array($atts) ? $atts : [],
            'postbag_signup'
        );

        return $this->render(
            [
                'listId' => (string) $atts['list'],
                'title' => (string) $atts['title'],
                'buttonText' => (string) $atts['button'],
                'showName' => $atts['show_name'] === 'yes',
            ]
        );
    }

    /**
     * The form markup. Every value is escaped on output.
     *
     * @param  array<string, mixed>  $attributes  listId, title, buttonText, showName.
     */
    public function render(array $attributes): string
    {
        wp_enqueue_script('postbag-signup');
        wp_enqueue_style('postbag-signup');

        $list_id = Settings::sanitize_uuid($attributes['listId'] ?? '');
        $list_id = $list_id !== '' ? $list_id : $this->settings->get('list_id');
        $id = wp_unique_id('postbag-signup-');

        ob_start();
        ?>
		<form class="postbag-signup" method="post" action="<?php echo esc_url(rest_url('postbag/v1/subscribe')); ?>" data-postbag-signup novalidate>
			<?php if (! empty($attributes['title'])) { ?>
				<p class="postbag-signup__title"><?php echo esc_html((string) $attributes['title']); ?></p>
			<?php } ?>
			<input type="hidden" name="list" value="<?php echo esc_attr($list_id); ?>">
			<input type="hidden" name="started" value="<?php echo esc_attr((string) time()); ?>">
			<?php /* Honeypot: hidden from people, filled in by bots. */ ?>
			<div class="postbag-signup__hp" aria-hidden="true">
				<label for="<?php echo esc_attr($id); ?>-website">Website</label>
				<input type="text" id="<?php echo esc_attr($id); ?>-website" name="website" tabindex="-1" autocomplete="off">
			</div>
			<div class="postbag-signup__fields">
				<?php if (! empty($attributes['showName'])) { ?>
					<label class="screen-reader-text" for="<?php echo esc_attr($id); ?>-name"><?php esc_html_e('Name', 'postbag-signup'); ?></label>
					<input type="text" id="<?php echo esc_attr($id); ?>-name" name="name" autocomplete="name" placeholder="<?php esc_attr_e('Your name', 'postbag-signup'); ?>">
				<?php } ?>
				<label class="screen-reader-text" for="<?php echo esc_attr($id); ?>-email"><?php esc_html_e('Email address', 'postbag-signup'); ?></label>
				<input type="email" id="<?php echo esc_attr($id); ?>-email" name="email" autocomplete="email" required placeholder="<?php esc_attr_e('you@example.com', 'postbag-signup'); ?>">
				<button type="submit"><?php echo esc_html((string) ($attributes['buttonText'] ?? __('Subscribe', 'postbag-signup'))); ?></button>
			</div>
			<p class="postbag-signup__message" role="status" aria-live="polite"></p>
		</form>
		<?php
        return (string) ob_get_clean();
    }
}

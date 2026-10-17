<?php
/**
 * Front-end output of the Newsletter signup block.
 *
 * @package PostbagSignup
 *
 * @var array<string, mixed> $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$postbag_form = new Postbag_Signup\Form( new Postbag_Signup\Settings() );
printf(
	'<div %s>%s</div>',
	get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress.
	$postbag_form->render( $attributes ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value is escaped in Form::render().
);

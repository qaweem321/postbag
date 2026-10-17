<?php

use Postbag_Signup\Form;
use Postbag_Signup\Settings;

/**
 * Front-end output of the Newsletter signup block.
 *
 *
 * @var array<string, mixed> $attributes Block attributes.
 */
defined('ABSPATH') || exit;

$postbag_form = new Form(new Settings);
printf(
    '<div %s>%s</div>',
    get_block_wrapper_attributes(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress.
    $postbag_form->render($attributes) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- every value is escaped in Form::render().
);

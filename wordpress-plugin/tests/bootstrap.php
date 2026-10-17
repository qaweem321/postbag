<?php

/**
 * Unit tests run without WordPress: Brain Monkey stubs the functions the plugin calls.
 */

require dirname(__DIR__).'/vendor/autoload.php';

define('ABSPATH', '/tmp/wordpress/');
define('MINUTE_IN_SECONDS', 60);
define('POSTBAG_SIGNUP_VERSION', '1.0.0');

/** Minimal stand-in for WordPress's WP_Error. */
class WP_Error
{
    public function __construct(public string $code = '', public string $message = '', public mixed $data = null) {}

    public function get_error_code(): string
    {
        return $this->code;
    }

    public function get_error_message(): string
    {
        return $this->message;
    }

    public function get_error_data(): mixed
    {
        return $this->data;
    }
}

require dirname(__DIR__).'/includes/class-api-client.php';
require dirname(__DIR__).'/includes/class-settings.php';
require dirname(__DIR__).'/includes/class-signup.php';

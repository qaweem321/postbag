<?php

namespace Postbag_Signup\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /** @var array<string, mixed> fake transient store */
    protected array $transients = [];

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();
        Functions\stubs(
            [
                'is_wp_error' => static fn ($thing) => $thing instanceof \WP_Error,
                'sanitize_email' => static fn ($email) => trim((string) $email),
                'is_email' => static fn ($email) => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
                'sanitize_text_field' => static fn ($text) => trim(strip_tags((string) $text)),
                'wp_unslash' => static fn ($value) => $value,
                'untrailingslashit' => static fn ($value) => rtrim((string) $value, '/\\'),
                'wp_salt' => 'salt',
                'get_transient' => fn ($key) => $this->transients[$key] ?? false,
                'set_transient' => function ($key, $value) {
                    $this->transients[$key] = $value;

                    return true;
                },
                'wp_remote_retrieve_response_code' => static fn ($response) => $response['response']['code'] ?? '',
                'wp_remote_retrieve_body' => static fn ($response) => $response['body'] ?? '',
            ]
        );
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }
}

<?php

namespace Postbag_Signup\Tests;

use Postbag_Signup\Api_Client;

require_once __DIR__.'/TestCase.php';

final class ApiClientTest extends TestCase
{
    private static function response(int $code, array $body): array
    {
        return [
            'response' => ['code' => $code],
            'body' => json_encode($body),
        ];
    }

    public function test_success_returns_the_decoded_body(): void
    {
        $result = Api_Client::parse_response(self::response(201, ['outcome' => 'subscribed']));

        $this->assertSame(['outcome' => 'subscribed'], $result);
    }

    public function test_validation_errors_show_the_api_message(): void
    {
        $result = Api_Client::parse_response(self::response(422, ['errors' => ['email' => ['The email field must be a valid email address.']]]));

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('The email field must be a valid email address.', $result->get_error_message());
    }

    public function test_auth_failures_do_not_leak_details_to_visitors(): void
    {
        $result = Api_Client::parse_response(self::response(401, ['message' => 'Unauthenticated.']));

        $this->assertSame('postbag_auth', $result->get_error_code());
        $this->assertStringNotContainsString('Unauthenticated', $result->get_error_message());
    }

    public function test_network_errors_and_rate_limits_get_friendly_messages(): void
    {
        $this->assertSame('postbag_unreachable', Api_Client::parse_response(new \WP_Error('http_request_failed', 'cURL error 28'))->get_error_code());
        $this->assertSame('postbag_busy', Api_Client::parse_response(self::response(429, []))->get_error_code());
        $this->assertSame('postbag_error', Api_Client::parse_response(self::response(500, []))->get_error_code());
    }

    public function test_an_unconfigured_client_does_not_send_requests(): void
    {
        $client = new Api_Client('', '');

        $this->assertFalse($client->is_configured());
        $this->assertSame('postbag_not_configured', $client->subscribe('list', 'a@example.com')->get_error_code());
    }
}

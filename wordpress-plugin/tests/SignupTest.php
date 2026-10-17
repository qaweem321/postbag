<?php

namespace Postbag_Signup\Tests;

use Brain\Monkey\Functions;
use Postbag_Signup\Api_Client;
use Postbag_Signup\Settings;
use Postbag_Signup\Signup;

require_once __DIR__.'/TestCase.php';

final class SignupTest extends TestCase
{
    private const LIST_ID = '01a14850-b7d0-70ae-b6c1-edf189699cdd';

    /** @var array<int, array<string, string>> */
    private array $calls = [];

    private function signup(array|\WP_Error $api_result = ['outcome' => 'pending_confirmation']): Signup
    {
        Functions\when('get_option')->justReturn(['list_id' => self::LIST_ID]);
        $client = new class($api_result, $this->calls) extends Api_Client
        {
            public function __construct(private $result, private array &$calls) {}

            public function subscribe(string $list_id, string $email, string $name = '')
            {
                $this->calls[] = compact('list_id', 'email', 'name');

                return $this->result;
            }
        };

        return new Signup($client, new Settings);
    }

    private function valid(array $overrides = []): array
    {
        return array_merge(['email' => 'jane@example.com', 'started' => time() - 10], $overrides);
    }

    public function test_subscribes_to_the_default_list(): void
    {
        $message = $this->signup()->handle($this->valid(['name' => '<b>Jane</b>']), '203.0.113.5');

        $this->assertSame('Thanks! Check your inbox to confirm your subscription.', $message);
        $this->assertSame([['list_id' => self::LIST_ID, 'email' => 'jane@example.com', 'name' => 'Jane']], $this->calls);
    }

    public function test_a_list_in_the_form_overrides_the_default(): void
    {
        $other = '01a14850-b7d0-70ae-b6c1-000000000000';
        $this->signup()->handle($this->valid(['list' => strtoupper($other)]), 'ip');

        $this->assertSame($other, $this->calls[0]['list_id']);
    }

    public function test_honeypot_and_instant_submissions_look_successful_but_send_nothing(): void
    {
        $signup = $this->signup();

        $this->assertIsString($signup->handle($this->valid(['website' => 'http://spam.example']), 'ip'));
        $this->assertIsString($signup->handle($this->valid(['started' => time()]), 'ip'));
        $this->assertSame([], $this->calls);
    }

    public function test_rejects_invalid_email(): void
    {
        $result = $this->signup()->handle($this->valid(['email' => 'not-an-email']), 'ip');

        $this->assertSame('postbag_invalid_email', $result->get_error_code());
        $this->assertSame(['status' => 422], $result->get_error_data());
    }

    public function test_limits_attempts_per_ip(): void
    {
        $signup = $this->signup();
        for ($i = 0; $i < Signup::RATE_LIMIT; $i++) {
            $this->assertIsString($signup->handle($this->valid(), '203.0.113.5'));
        }

        $this->assertSame('postbag_rate_limited', $signup->handle($this->valid(), '203.0.113.5')->get_error_code());
        $this->assertIsString($signup->handle($this->valid(), '198.51.100.7'));
    }

    public function test_explains_each_outcome(): void
    {
        $this->assertSame('You are already subscribed. Thanks!', $this->signup(['outcome' => 'already_subscribed'])->handle($this->valid(), 'a'));
        $this->assertSame('Thanks for subscribing!', $this->signup(['outcome' => 'subscribed'])->handle($this->valid(), 'b'));
    }

    public function test_passes_api_errors_through(): void
    {
        $result = $this->signup(new \WP_Error('postbag_busy', 'Too many signups right now.'))->handle($this->valid(), 'ip');

        $this->assertSame('postbag_busy', $result->get_error_code());
    }
}

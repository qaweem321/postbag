<?php

namespace Postbag_Signup\Tests;

use Brain\Monkey\Functions;
use Postbag_Signup\Settings;

require_once __DIR__.'/TestCase.php';

final class SettingsTest extends TestCase
{
    public function test_list_ids_must_be_uuids(): void
    {
        $this->assertSame('01a14850-b7d0-70ae-b6c1-edf189699cdd', Settings::sanitize_uuid(' 01A14850-B7D0-70AE-B6C1-EDF189699CDD '));
        $this->assertSame('', Settings::sanitize_uuid('1 OR 1=1'));
    }

    public function test_an_empty_token_field_keeps_the_saved_token(): void
    {
        Functions\when('get_option')->justReturn(['api_token' => 'saved-token']);
        Functions\when('esc_url_raw')->returnArg();

        $clean = (new Settings)->sanitize(['api_url' => 'https://news.example.com', 'api_token' => '', 'list_id' => 'bad']);

        $this->assertSame(['api_url' => 'https://news.example.com', 'api_token' => 'saved-token', 'list_id' => ''], $clean);
    }
}

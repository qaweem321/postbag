<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)->use(RefreshDatabase::class)->in('Feature', 'Unit');

/** Logs in a dashboard user for the test. */
function login(): User
{
    $user = User::factory()->create();
    test()->actingAs($user);

    return $user;
}

/** A Sanctum token header with the given abilities. */
function apiToken(array $abilities = ['read', 'write']): array
{
    $token = User::factory()->create()->createToken('test', $abilities)->plainTextToken;

    return ['Authorization' => "Bearer {$token}", 'Accept' => 'application/json'];
}

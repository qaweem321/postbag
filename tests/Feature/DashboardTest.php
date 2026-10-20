<?php

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\MailingList;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

it('sends guests to login and back to the page they asked for', function () {
    $this->get('/campaigns')->assertRedirect('/login');

    $user = User::factory()->create(['email' => 'admin@example.com', 'password' => 'secret-password']);
    $this->post('/login', ['email' => 'ADMIN@example.com', 'password' => 'secret-password'])->assertRedirect('/campaigns');
    $this->assertAuthenticatedAs($user);

    // Logged in: the login page goes to the dashboard.
    $this->get('/login')->assertRedirect('/');
});

it('renders the login password field with a show/hide toggle', function () {
    $this->get('/login')->assertOk()
        ->assertSee('type="password"', false)
        ->assertSee('data-password-toggle', false)
        ->assertSee('aria-label="Show password"', false);
});

it('offers a System, Light and Dark theme in settings and sets it before paint', function () {
    $this->actingAs(User::factory()->create())->get('/settings')->assertOk()
        ->assertSee('data-theme-select', false)
        ->assertSeeInOrder(['value="system"', 'value="light"', 'value="dark"'], false)
        ->assertSee("localStorage.getItem('theme')", false);
    auth()->logout();
    $this->get('/settings')->assertRedirect('/login');
    $this->get('/login')->assertSee("localStorage.getItem('theme')", false);
});

it('rejects wrong passwords and rate-limits guessing', function () {
    User::factory()->create(['email' => 'admin@example.com']);
    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertSessionHasErrors('email');
    }
    $this->post('/login', ['email' => 'admin@example.com', 'password' => 'wrong'])->assertTooManyRequests();
});

it('logs out and marks dashboard pages as not cacheable', function () {
    login();
    expect($this->get('/')->assertOk()->headers->get('Cache-Control'))->toContain('no-store');
    $this->post('/logout')->assertRedirect('/login');
    $this->get('/')->assertRedirect('/login');
});

it('creates a list, adds a subscriber and shows them', function () {
    login();
    Mail::fake();

    $this->post('/lists', ['name' => 'Weekly digest', 'double_opt_in' => '1'])->assertRedirect();
    $list = MailingList::query()->sole();
    $this->post("/lists/{$list->uuid}/subscribers", ['email' => 'jane@example.com', 'skip_confirmation' => '1'])
        ->assertSessionHas('status', 'jane@example.com is subscribed.');

    $this->get("/lists/{$list->uuid}")->assertOk()->assertSee('jane@example.com')->assertSee('1 subscribed');
    $this->get("/lists/{$list->uuid}?q=nobody")->assertOk()->assertSee('No subscribers match these filters');
});

it('refuses to delete a list that has campaigns', function () {
    login();
    $campaign = Campaign::factory()->create();

    $this->delete("/lists/{$campaign->mailingList->uuid}")->assertSessionHasErrors('list');
    expect(MailingList::query()->count())->toBe(1);
});

it('creates, previews and sends a campaign', function () {
    login();
    Mail::fake();
    $list = MailingList::factory()->create();
    Subscription::factory()->count(2)->for($list)->create();

    $this->post('/campaigns', [
        'name' => 'Launch', 'list' => $list->uuid, 'subject' => 'We launched',
        'from_name' => 'Team', 'from_email' => 'team@example.com', 'body_markdown' => "Hi {{ name }}!\n\n<script>alert(1)</script>",
    ])->assertRedirect();
    $campaign = Campaign::query()->sole();

    $page = $this->get("/campaigns/{$campaign->uuid}")->assertOk()->assertSee('Goes to 2 subscribed people');
    // Raw HTML in the Markdown is escaped, not rendered.
    expect($page->getContent())->not->toContain('<script>alert(1)</script>');

    $this->post("/campaigns/{$campaign->uuid}/send")->assertRedirect();
    expect($campaign->fresh()->status)->toBe(CampaignStatus::Sent);
    $this->put("/campaigns/{$campaign->uuid}", ['name' => 'x'])->assertSessionHasErrors();
    $this->post("/campaigns/{$campaign->uuid}/send")->assertSessionHasErrors('campaign');
});

it('serves live campaign statistics for the page to poll', function () {
    login();
    Mail::fake();
    $list = MailingList::factory()->create();
    Subscription::factory()->count(2)->for($list)->create();
    $campaign = Campaign::factory()->for($list)->create();

    // Drafts have no statistics yet.
    $this->get("/campaigns/{$campaign->uuid}/stats")->assertNotFound();

    $this->post("/campaigns/{$campaign->uuid}/send");
    $this->get("/campaigns/{$campaign->uuid}")->assertSee('id="campaign-stats"', false);
    $this->get("/campaigns/{$campaign->uuid}/stats")->assertOk()
        ->assertSee('data-live-key="sent"', false)
        ->assertSee('data-live-every="15000"', false)
        ->assertSee('Delivered')
        ->assertDontSee('<html', false);

    auth()->logout();
    $this->get("/campaigns/{$campaign->uuid}/stats")->assertRedirect('/login');
});

it('refreshes the campaigns list and dashboard only while a campaign is sending', function () {
    login();
    $campaign = Campaign::factory()->create();

    $this->get('/campaigns')->assertSee('id="campaign-table"', false)->assertDontSee('data-live-every', false);
    $this->get('/')->assertSee('id="dashboard-live"', false)->assertDontSee('data-live-every', false);

    $campaign->forceFill(['status' => CampaignStatus::Sending])->save();
    $this->get('/campaigns')->assertSee('data-live-every="3000"', false);
    $this->get('/')->assertSee('data-live-every="3000"', false);
});

it('counts people who subscribed after the list page loaded', function () {
    login();
    $list = MailingList::factory()->create();
    $shown = Subscription::factory()->for($list)->create();
    Subscription::factory()->for(MailingList::factory())->create(); // another list

    $this->get("/lists/{$list->uuid}")->assertSee("after={$shown->uuid}", false);
    $this->getJson("/lists/{$list->uuid}/new-subscribers?after={$shown->uuid}")->assertExactJson(['count' => 0]);

    Subscription::factory()->count(2)->for($list)->create();
    $this->getJson("/lists/{$list->uuid}/new-subscribers?after={$shown->uuid}")->assertExactJson(['count' => 2]);
    $this->getJson("/lists/{$list->uuid}/new-subscribers?after=not-a-uuid")->assertUnprocessable();
});

it('schedules in UTC and unschedules', function () {
    login();
    $campaign = Campaign::factory()->create();

    $this->post("/campaigns/{$campaign->uuid}/schedule", ['scheduled_at' => now()->subHour()->format('Y-m-d\TH:i')])->assertSessionHasErrors('scheduled_at');
    $this->post("/campaigns/{$campaign->uuid}/schedule", ['scheduled_at' => '2030-01-02T09:30'])->assertSessionHas('status', 'Scheduled for Jan 2, 2030, 09:30 UTC.');
    expect($campaign->fresh()->status)->toBe(CampaignStatus::Scheduled);

    $this->post("/campaigns/{$campaign->uuid}/unschedule");
    expect($campaign->fresh())->status->toBe(CampaignStatus::Draft)->scheduled_at->toBeNull();
});

it('creates API tokens shown once, and revokes them', function () {
    $user = login();

    $this->post('/tokens', ['name' => 'WordPress', 'abilities' => ['write']])->assertSessionHas('new_token');
    $token = $user->tokens()->sole();
    expect($token->abilities)->toBe(['write']);

    $this->delete("/tokens/{$token->id}")->assertSessionHas('status');
    expect($user->tokens()->count())->toBe(0);
});

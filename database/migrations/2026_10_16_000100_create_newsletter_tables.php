<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mailing_lists', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('double_opt_in')->default(true);
            $table->timestamps();
        });

        // One row per email address. A bounce or spam complaint suppresses the address for every list.
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('email')->unique();
            $table->string('name')->nullable();
            $table->string('status', 16)->default('active'); // active, bounced, complained
            $table->timestamp('suppressed_at')->nullable();
            $table->timestamps();
        });

        // A subscriber's membership of one list.
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('mailing_list_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16); // pending, subscribed, unsubscribed
            $table->string('source', 32)->default('api'); // api, dashboard, import, wordpress
            // Re-sending the confirmation email is throttled, so a public form can't be used to flood an inbox.
            $table->timestamp('confirmation_sent_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->unique(['mailing_list_id', 'subscriber_id']);
            // Who gets a campaign: every subscribed member of a list, in id order.
            $table->index(['mailing_list_id', 'status', 'subscriber_id']);
        });

        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('mailing_list_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('subject');
            $table->string('from_name');
            $table->string('from_email');
            $table->longText('body_markdown');
            $table->string('status', 16)->default('draft'); // draft, scheduled, sending, sent, cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('recipients_count')->default(0);
            $table->string('batch_id', 36)->nullable(); // the queued job batch sending it
            $table->timestamps();
            $table->index(['status', 'scheduled_at']);
        });

        // One row per campaign and recipient. The unique key is what makes sending idempotent:
        // a recipient can only ever be queued once per campaign.
        Schema::create('campaign_sends', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscriber_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $table->string('status', 16)->default('pending'); // pending, sending, sent, failed, skipped
            $table->string('error')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamp('clicked_at')->nullable();
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();
            $table->unique(['campaign_id', 'subscriber_id']);
            $table->index(['campaign_id', 'status']);
        });

        Schema::create('campaign_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('url', 2048);
            $table->char('url_hash', 64);
            $table->unsignedInteger('click_count')->default(0);
            $table->timestamps();
            $table->unique(['campaign_id', 'url_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_links');
        Schema::dropIfExists('campaign_sends');
        Schema::dropIfExists('campaigns');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscribers');
        Schema::dropIfExists('mailing_lists');
    }
};

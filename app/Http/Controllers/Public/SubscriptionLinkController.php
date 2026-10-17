<?php

namespace App\Http\Controllers\Public;

use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Services\SubscriptionManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

/**
 * The links in emails. Opening a link only shows a page with a button; the change happens on
 * the POST. Mail scanners open every link in an email, and must not confirm or unsubscribe
 * anyone by doing so.
 */
class SubscriptionLinkController extends Controller
{
    public function __construct(private SubscriptionManager $subscriptions) {}

    public function showConfirm(Request $request, Subscription $subscription): View
    {
        return view('public.confirm', [
            'subscription' => $subscription->load('mailingList', 'subscriber'),
            'action' => $request->fullUrl(),
        ]);
    }

    public function confirm(Subscription $subscription): View
    {
        $this->subscriptions->confirm($subscription);
        $subscription->load('mailingList');

        return view('public.message', [
            'title' => 'You\'re subscribed',
            'message' => $subscription->status === SubscriptionStatus::Subscribed
                ? "Thanks! You'll now receive {$subscription->mailingList->name}."
                : 'This subscription was cancelled earlier, so it wasn\'t confirmed. Sign up again to resubscribe.',
        ]);
    }

    public function showUnsubscribe(Request $request, Subscription $subscription): View
    {
        return view('public.unsubscribe', [
            'subscription' => $subscription->load('mailingList', 'subscriber'),
            'action' => $request->fullUrl(),
        ]);
    }

    /** Also the RFC 8058 one-click endpoint: mail providers POST here without a CSRF token. */
    public function unsubscribe(Request $request, Subscription $subscription): View|Response
    {
        $this->subscriptions->unsubscribe($subscription);
        $subscription->load('mailingList');
        if ($request->input('List-Unsubscribe') === 'One-Click') {
            return response()->noContent();
        }

        return view('public.message', [
            'title' => 'You\'re unsubscribed',
            'message' => "You won't receive {$subscription->mailingList->name} any more.",
        ]);
    }
}

<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\CampaignStatus;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\MailingList;
use App\Models\Subscription;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $since = now()->subDays(30);

        return view('dashboard', [
            'subscribed' => Subscription::query()->where('status', SubscriptionStatus::Subscribed)->count(),
            'newThisMonth' => Subscription::query()->where('status', SubscriptionStatus::Subscribed)->where('confirmed_at', '>=', $since)->count(),
            'unsubscribedThisMonth' => Subscription::query()->where('unsubscribed_at', '>=', $since)->count(),
            'listCount' => MailingList::query()->count(),
            'recentCampaigns' => Campaign::query()->with('mailingList')->latest('id')->limit(5)->get(),
            'sentThisMonth' => Campaign::query()->where('status', CampaignStatus::Sent)->where('finished_at', '>=', $since)->count(),
        ]);
    }
}

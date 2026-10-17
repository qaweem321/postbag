<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\CampaignLink;
use App\Models\CampaignSend;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Open and click tracking. Both URLs are signed, so counts can't be inflated by guessing ids,
 * and a click only ever redirects to a link that is in the campaign (no open redirect).
 */
class TrackingController extends Controller
{
    private const PIXEL = 'R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

    public function open(CampaignSend $send): Response
    {
        // One UPDATE: counts the open and keeps the first time it happened.
        CampaignSend::query()->whereKey($send->id)->update([
            'open_count' => DB::raw('open_count + 1'),
            'opened_at' => DB::raw('COALESCE(opened_at, NOW())'),
        ]);

        return response(base64_decode(self::PIXEL), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-store, max-age=0',
        ]);
    }

    public function click(CampaignSend $send, CampaignLink $link): RedirectResponse
    {
        abort_unless($link->campaign_id === $send->campaign_id, 404);

        // A click proves the email was opened, even when images (the open pixel) were blocked.
        CampaignSend::query()->whereKey($send->id)->update([
            'click_count' => DB::raw('click_count + 1'),
            'clicked_at' => DB::raw('COALESCE(clicked_at, NOW())'),
            'opened_at' => DB::raw('COALESCE(opened_at, NOW())'),
        ]);
        CampaignLink::query()->whereKey($link->id)->increment('click_count');

        return redirect()->away($link->url);
    }
}

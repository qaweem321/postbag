<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CampaignRequest;
use App\Mail\CampaignMail;
use App\Models\Campaign;
use App\Models\MailingList;
use App\Services\CampaignRenderer;
use App\Services\CampaignSender;
use App\Services\CampaignStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class CampaignController extends Controller
{
    public function index(): View
    {
        return view('campaigns.index', [
            'campaigns' => Campaign::query()->with('mailingList')->latest('id')->paginate(25),
        ]);
    }

    public function create(Request $request): View
    {
        $campaign = new Campaign([
            'from_name' => config('postbag.from_name'),
            'from_email' => config('postbag.from_email'),
            'body_markdown' => "Hi {{ name }},\n\nWrite your newsletter here. **Markdown** works, and links are tracked: [example](https://example.com).\n",
        ]);

        return view('campaigns.form', [
            'campaign' => $campaign,
            'lists' => MailingList::query()->orderBy('name')->get(),
            'selectedList' => $request->query('list'),
        ]);
    }

    public function store(CampaignRequest $request): RedirectResponse
    {
        $campaign = Campaign::query()->create($request->attributesForModel());

        return redirect()->route('campaigns.show', $campaign)->with('status', 'Draft saved. Preview it and send a test before sending.');
    }

    public function show(Campaign $campaign, CampaignStats $stats, CampaignRenderer $renderer): View
    {
        $campaign->load('mailingList');

        return view('campaigns.show', [
            'campaign' => $campaign,
            // Shown in a sandboxed iframe (srcdoc) that can't run scripts. Raw HTML in the Markdown is
            // escaped anyway.
            'previewHtml' => $renderer->preview($campaign),
            'audience' => $campaign->mailingList->activeSubscriptions()->count(),
            'stats' => $campaign->status->isEditable() ? null : $stats->for($campaign),
        ]);
    }

    public function edit(Campaign $campaign): View|RedirectResponse
    {
        if (! $campaign->status->isEditable()) {
            return redirect()->route('campaigns.show', $campaign)->withErrors(['campaign' => 'A campaign can\'t be edited once it has started sending.']);
        }

        return view('campaigns.form', [
            'campaign' => $campaign->load('mailingList'),
            'lists' => MailingList::query()->orderBy('name')->get(),
            'selectedList' => $campaign->mailingList->uuid,
        ]);
    }

    public function update(CampaignRequest $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status->isEditable(), 409, 'This campaign has already started sending.');
        $campaign->update($request->attributesForModel());

        return redirect()->route('campaigns.show', $campaign)->with('status', 'Campaign saved.');
    }

    public function destroy(Campaign $campaign): RedirectResponse
    {
        abort_if($campaign->status === CampaignStatus::Sending, 409, 'Cancel the campaign before deleting it.');
        $campaign->delete();

        return redirect()->route('campaigns.index')->with('status', "Campaign \"{$campaign->name}\" deleted.");
    }

    public function sendTest(Request $request, Campaign $campaign, CampaignRenderer $renderer): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'string', 'email:rfc', 'max:254']]);
        $campaign->load('mailingList');
        $html = $renderer->preview($campaign);
        Mail::to($data['email'])->send(new CampaignMail($campaign, $html, $campaign->body_markdown, url('/')));

        return back()->with('status', "Test email sent to {$data['email']}.");
    }

    public function send(Campaign $campaign, CampaignSender $sender): RedirectResponse
    {
        if (! $sender->start($campaign)) {
            return back()->withErrors(['campaign' => 'This campaign has already started.']);
        }

        return redirect()->route('campaigns.show', $campaign)->with('status', 'Sending started. This page shows progress.');
    }

    public function schedule(Request $request, Campaign $campaign): RedirectResponse
    {
        abort_unless($campaign->status->isEditable(), 409);
        $data = $request->validate(['scheduled_at' => ['required', 'date', 'after:now']], [
            'scheduled_at.after' => 'Pick a time in the future.',
        ]);
        // The form sends UTC (the dashboard shows all times in UTC).
        $campaign->forceFill([
            'status' => CampaignStatus::Scheduled,
            'scheduled_at' => Carbon::parse($data['scheduled_at'], 'UTC'),
        ])->save();

        return back()->with('status', 'Scheduled for '.$campaign->scheduled_at?->format('M j, Y, H:i').' UTC.');
    }

    public function unschedule(Campaign $campaign): RedirectResponse
    {
        Campaign::query()->whereKey($campaign->id)->where('status', CampaignStatus::Scheduled)
            ->update(['status' => CampaignStatus::Draft, 'scheduled_at' => null]);

        return back()->with('status', 'Back to draft: it won\'t be sent until you send or schedule it again.');
    }

    public function cancel(Campaign $campaign, CampaignSender $sender): RedirectResponse
    {
        abort_unless($campaign->status === CampaignStatus::Sending, 409);
        $sender->cancel($campaign);

        return back()->with('status', 'Sending cancelled. Emails already sent can\'t be recalled.');
    }
}

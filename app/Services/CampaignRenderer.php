<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\CampaignSend;
use App\Models\Subscriber;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * Turns a campaign's Markdown into the email one subscriber receives: personalized, with
 * tracked links, an open pixel and an unsubscribe link.
 */
class CampaignRenderer
{
    /** @var array<int, string> rendered Markdown per campaign, so a batch converts it once */
    private array $htmlCache = [];

    /** Markdown to HTML. Raw HTML in the source is escaped and unsafe links (javascript:) are dropped. */
    public function bodyHtml(Campaign $campaign): string
    {
        return $this->htmlCache[$campaign->id] ??= Str::markdown($campaign->body_markdown, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);
    }

    /** @return list<string> every http(s) link in the campaign, in order of appearance */
    public function links(Campaign $campaign): array
    {
        preg_match_all('/href="(https?:\/\/[^"]+)"/i', $this->bodyHtml($campaign), $matches);

        return array_values(array_unique(array_map(fn (string $url) => html_entity_decode($url), $matches[1])));
    }

    /**
     * @param  array<string, int>  $linkIds  url => campaign_links.id
     * @return array{html: string, text: string, unsubscribe_url: string}
     */
    public function render(Campaign $campaign, CampaignSend $send, Subscriber $subscriber, array $linkIds): array
    {
        $unsubscribeUrl = URL::signedRoute('subscriptions.unsubscribe', ['subscription' => $send->subscription->uuid]);

        $html = $this->personalize($this->bodyHtml($campaign), $subscriber, escape: true);
        $html = preg_replace_callback('/href="(https?:\/\/[^"]+)"/i', function (array $match) use ($send, $linkIds) {
            $id = $linkIds[html_entity_decode($match[1])] ?? null;

            return $id === null ? $match[0] : 'href="'.e(URL::signedRoute('track.click', ['send' => $send->uuid, 'link' => $id])).'"';
        }, $html) ?? $html;

        return [
            'html' => view('emails.campaign', [
                'subject' => $campaign->subject,
                'body' => $html,
                'listName' => $campaign->mailingList->name,
                'unsubscribeUrl' => $unsubscribeUrl,
                'pixelUrl' => URL::signedRoute('track.open', ['send' => $send->uuid]),
            ])->render(),
            'text' => $this->personalize($campaign->body_markdown, $subscriber, escape: false)
                ."\n\n--\nYou receive this because you subscribed to {$campaign->mailingList->name}.\nUnsubscribe: {$unsubscribeUrl}\n",
            'unsubscribe_url' => $unsubscribeUrl,
        ];
    }

    /** A preview for the dashboard and test emails: no tracking, placeholder personalization. */
    public function preview(Campaign $campaign, ?Subscriber $subscriber = null): string
    {
        $subscriber ??= new Subscriber(['email' => 'jane@example.com', 'name' => 'Jane']);

        return view('emails.campaign', [
            'subject' => $campaign->subject,
            'body' => $this->personalize($this->bodyHtml($campaign), $subscriber, escape: true),
            'listName' => $campaign->mailingList->name,
            'unsubscribeUrl' => '#',
            'pixelUrl' => null,
        ])->render();
    }

    /** Fills in {{ name }} and {{ email }}. Values are HTML-escaped in the HTML version. */
    private function personalize(string $content, Subscriber $subscriber, bool $escape): string
    {
        $values = ['name' => $subscriber->name ?: 'there', 'email' => $subscriber->email];

        return preg_replace_callback('/\{\{\s*(name|email)\s*\}\}/', function (array $match) use ($values, $escape) {
            return $escape ? e($values[$match[1]]) : $values[$match[1]];
        }, $content) ?? $content;
    }
}

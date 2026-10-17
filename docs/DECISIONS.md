# Design decisions

Why Postbag is built the way it is, and what each choice costs. Each section answers a question an
interviewer or contributor is likely to ask.

## 1. How does a campaign avoid emailing someone twice?

Three layers:

1. **Starting is atomic.** `CampaignSender::start()` locks the campaign row (`SELECT ... FOR UPDATE`),
   checks it is still a draft or scheduled, and flips it to `sending` in the same transaction. A
   double click, or the scheduler firing at the same moment, finds it already started.
2. **One row per recipient.** The recipient list is frozen with a single
   `INSERT IGNORE ... SELECT` into `campaign_sends`, which has a unique key on
   `(campaign_id, subscriber_id)`. A person can only be queued once per campaign, however large
   the list, and the insert is one statement instead of one query per subscriber.
3. **Each send is claimed before it is sent.** A batch job flips a send from `pending` to
   `sending` with a conditional `UPDATE ... WHERE status = 'pending'`; only the job that changed
   the row sends the email. A retried or duplicated job skips everything already claimed.

**Trade-off: at most once, not at least once.** If a worker dies after claiming a send but before
recording the result, that send stays `sending` and is never retried, because the email may already
have left. For newsletters a missed email is better than a duplicate. (Hookrelay, a webhook relay
in another of my repos, makes the opposite choice, because webhooks must arrive.)

## 2. How is sending spread over time?

Sends are split into jobs of 100 (`POSTBAG_BATCH_SIZE`) inside a Laravel job batch. Jobs go through a
rate limiter (`POSTBAG_EMAILS_PER_MINUTE`, default 600), so any number of queue workers together stay
under the mail provider's limit. Because rate limiting releases jobs back onto the queue, retries are
bounded by time (`retryUntil`: one day) and by real errors (`maxExceptions`: 3) rather than by a
fixed attempt count. The batch's `finally` callback marks the campaign sent when every job has
finished or failed for good.

## 3. Why are people re-checked at send time?

The recipient list is frozen when sending starts, but a campaign to 100,000 people can take hours.
Each job re-checks the subscription and the subscriber before sending: someone who unsubscribed, or
whose address bounced, in the meantime is skipped (`skipped` status) instead of emailed.

## 4. How does double opt-in work, and why do the links need a POST?

New subscribers on a double opt-in list stay `pending` and get a confirmation email with a signed link
that expires after a week. Opening the link only shows a page with a button; the subscription changes
on the POST. Corporate mail scanners and some email apps open every link in an email, and must not
confirm (or unsubscribe) anyone by doing so.

Re-sending the confirmation email is limited to once per 15 minutes per address, so a public signup form
can't be used to flood someone's inbox.

## 5. How does unsubscribing work?

Every campaign email has an unsubscribe link in the footer and the `List-Unsubscribe` and
`List-Unsubscribe-Post: List-Unsubscribe=One-Click` headers (RFC 8058). Gmail and Yahoo require these
from bulk senders and show their own unsubscribe button. That button sends a POST without a CSRF
token, so the route is excluded from CSRF checks; the URL's signature is what authorizes it, and an
unsigned or edited URL gets a 403.

## 6. Why signed URLs for tracking?

The open pixel (`/t/o/...`) and click links (`/t/c/...`) carry Laravel URL signatures, so nobody can
inflate statistics by guessing ids. A click redirects only to a link that belongs to the same campaign,
so the click endpoint can't be used as an open redirect. Counting is one atomic `UPDATE` per event
(`open_count = open_count + 1`, `opened_at = COALESCE(opened_at, NOW())`), with no read-modify-write
race.

**Open rates are approximate,** and the dashboard says so: Apple Mail Privacy Protection loads every
image (counted as opens), while other apps block images (not counted). A click also counts as an open.

## 7. How are bounces and spam complaints handled?

`POST /api/v1/suppressions` marks an address `bounced` or `complained`. A suppressed address is
excluded from every list's sends and can't be subscribed again. Mail providers each have their own
webhook format, so the intended setup is a small adapter (or the provider's webhook transformer) that
calls this endpoint.

## 8. Why these MySQL indexes?

- `subscriptions (mailing_list_id, status, subscriber_id)` serves the recipient query of
  `INSERT ... SELECT` and the per-list counts.
- `campaign_sends (campaign_id, status)` serves the statistics query and finding pending sends.
- `subscribers.email` is unique, and emails are stored lowercase, so lookups ignore case without a
  function index.

Statistics come from one aggregate query (`SUM(status = 'sent')`, `SUM(opened_at IS NOT NULL)`, ...)
instead of one count per number. Subscriber search in the dashboard uses `LIKE '%term%'`, which can't use
an index; fine for tens of thousands of subscribers, but a full-text index would be next.

## 9. Why UUIDs in URLs and the API?

Lists, subscriptions, campaigns and sends are addressed by UUID (`HasUuids`, ordered v7), so ids in
emails and API calls can't be guessed or counted. Auto-increment ids stay the primary keys, which keeps
joins and indexes compact.

## 10. Why does the WordPress plugin call its own site, not Postbag?

The signup form posts to `/wp-json/postbag/v1/subscribe` on the WordPress site, which calls Postbag
server to server. The API token never reaches the browser. The form has no nonce, so it works on
cached pages; abuse is limited by a honeypot field, a minimum fill-in time and a per-IP rate limit
(the IP is hashed before it is used as a transient key).

## 11. Why is everything in Docker?

PHP, Composer, MySQL, the queue worker, the scheduler, Mailpit and a demo WordPress site all run in
Docker Compose, so the only requirement is Docker. One image serves three roles (web, worker,
scheduler) with different commands. Development uses PHP's built-in server; the production target runs
Apache as an unprivileged user with OPcache.

## 12. What would change at larger scale?

- The database queue and cache are fine for one server; Redis would take that load off MySQL.
- The rate limiter counts jobs per minute, not emails, so the effective limit is rounded to whole batches.
- Statistics are computed on demand; for very large campaigns they would be cached or pre-aggregated.

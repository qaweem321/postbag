=== Postbag Signup ===
Contributors: qaweem321
Tags: newsletter, email, signup form, subscribe, double opt-in
Requires at least: 6.6
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.0.0
License: MIT

Newsletter signup forms (a block and a shortcode) that add subscribers to a self-hosted Postbag list.

== Description ==

Postbag is a self-hosted newsletter platform. This plugin puts its signup form on your WordPress site:

* **Newsletter signup** block, with title, button text and optional name field.
* `[postbag_signup list="…" title="…" button="…" show_name="yes"]` shortcode for classic themes.
* Double opt-in is handled by Postbag: new subscribers get a confirmation email.
* The API token stays on your server. Visitors' browsers only talk to your site.
* Spam protection without CAPTCHAs: a honeypot field, a minimum fill-in time and a per-visitor rate limit.
* Works on cached pages (no nonce in the form).
* WP-CLI: `wp postbag status` and `wp postbag subscribe <email>`.

== Installation ==

1. Copy the `postbag-signup` folder to `wp-content/plugins/` and activate it.
2. In Postbag, create an API token with the read and write permissions.
3. In WordPress, go to Settings → Postbag, enter your Postbag URL and the token, and pick a list.
4. Add the Newsletter signup block to a page.

== Changelog ==

= 1.0.0 =
* First release.

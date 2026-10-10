=== Mine Mirth Newsletter Alerts ===
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Emails you when someone signs up for the Mine Mirth newsletter, and fixes sign-ups that fail on cached pages. Works with the Mine Mirth theme as installed; no theme change needed.

== What it fixes ==

1. No notification. The theme saves each subscriber (Promotions › Subscribers) but never emails anyone. This plugin sends you an alert for every new sign-up: the email address, which form they used, the page they were on, the date and your total subscriber count. Reply to the alert to write straight to the subscriber.
2. Lost sign-ups on cached pages. The sign-up form carries a security token printed into the page. When your host or a cache plugin serves an older copy of the page, the token has expired and WordPress rejects the sign-up ("Cookie check failed"), so the subscriber is never saved. The plugin accepts those sign-ups; the theme's spam trap and rate limit still apply.

== Settings: Promotions › Sign-up alerts ==

* Send alerts to one or more addresses (default: the site admin email).
* Right away, or a daily summary at 8:00 (which also catches any alert that failed), or off.
* Optional welcome email to new subscribers, with your own subject and text.
* Optional "Send from" address (use one at minemirth.com).
* Send a test alert, see recent sign-ups (send any alert again) and an email log with the reason for any failure.
* A dashboard box shows your latest sign-ups, and a warning appears if alerts stop being delivered.

== If emails don't arrive ==

Most hosts don't deliver WordPress email reliably. Install a free SMTP plugin ("WP Mail SMTP" or "FluentSMTP"), connect it to your mailbox (for example support@minemirth.com), then click "Send a test alert".

If your newsletter forms send sign-ups to an external service (Customize › Newsletter forms › Where signups go), that service sends its own notifications instead.

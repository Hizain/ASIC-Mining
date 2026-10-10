=== Mine Mirth Mailer ===
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Send email to your subscribers in your website's own design: a weekly email built for each person from what they did on the site, plus campaigns you write yourself with ready-made email blocks. Opens, clicks and replies are recorded, and you answer replies from WordPress. Uses the subscribers the Mine Mirth theme already collects; no theme change needed.

== Where to find it ==

Everything is under the new "Mailer" menu: Overview, New email, Weekly email, Inbox, Subscribers, Settings.

== Before your first send ==

1. Install a free SMTP plugin ("WP Mail SMTP" or "FluentSMTP") and connect it to your mailbox or an email service. Most hosts don't deliver WordPress email reliably, and bulk email needs a proper sender.
2. Mailer › Settings: set "From email" to an address at minemirth.com, "Replies go to" (for example support@minemirth.com), and your business postal address. The address is required by law in marketing email and is shown in every footer.
3. Send yourself a test from any email's "Test & send" page.

The Overview page has a checklist that ticks these off.

== The weekly email ==

Turn it on in Mailer › Weekly email and pick the day and time. Each subscriber gets their own version, based on what they did on the site in the last 30 days:

* Looked at miners in the shop: "Still thinking about the …?" with those miners (current price and stock), similar miners to compare, the profit calculator or hosting, and a "Get a quote" button.
* Read the blog: new articles in the topics they read, this week's Bitcoin network numbers, two miners and the calculator.
* Used the calculators or hosting pages: network numbers, miners and hosting.
* Visited the homepage, or no activity yet: the best of everything: network numbers, new miners, new articles and hosting.

Sold-out products are never promoted. You can add a personal note for the week and set a fixed subject line, or leave it empty for the personal subjects. Preview any subscriber's version before it goes out, or send this week's email right away.

How visits are recorded: when someone subscribes, or clicks a link in one of your emails, their browser is remembered with a first-party cookie. Their page views on minemirth.com are then recorded against their subscription. It works with page caching. Visitors who never subscribed are not tracked. The suggested privacy policy text is added to Settings › Privacy › Policy guide.

== Writing your own emails ==

Mailer › New email opens the block editor with a starter layout. Besides normal text, headings, lists, images, buttons, quotes, columns and separators, you get these email blocks:

* Email hero: big headline, intro text, button and an optional image.
* Email products: product cards (image, hashrate and power, price, stock and a button). Choose newest, best sellers, on sale, featured, a category, or specific product IDs.
* Email articles: latest articles, or one category.
* Bitcoin network snapshot: price, hashrate, difficulty and the next adjustment at send time.
* Email call to action: dark card with headline, text and button.
* Ask us anything: invites a reply; answers land in Mailer › Inbox.

Write {first_name} anywhere (or {first_name|friend} for a fallback). The name comes from their account or orders.

In the "Email" box on the right, set the subject, the preview text (shown after the subject in the inbox) and who it goes to: everyone, shop visitors, blog readers, tool users, engaged, inactive, customers or non-customers. Then click "Test & send…" to send a test, send now or schedule it. Emails go out in batches every minute (Settings › Emails per minute), so large lists never overload your mail service.

Each email's page shows sent, opened, clicked, replies and unsubscribes, plus the list of recipients. Duplicate an email from the list to reuse its layout.

== Replies and the inbox ==

Every email has a reply button. It opens a short form on your website (topic, message, optional phone), already linked to the subscriber, so nothing needs to be typed twice. Messages appear in Mailer › Inbox and you get an email alert. Answer from the conversation page: your reply is sent in the website's email design with a Reply button, so the conversation continues in the inbox.

Replies people send from their mail app go to your "Replies go to" mailbox. To also copy those into the inbox, fill in the optional IMAP settings (use an app password). This needs the PHP IMAP extension on your server. The settings page tells you if it's missing.

== Subscribers ==

Mailer › Subscribers lists everyone with their status. Open a subscriber to see their interests, the miners they viewed, what they bought, their full activity (visits, opens, clicks, replies), and buttons to message them or preview their weekly email.

== Unsubscribing ==

Every email has an unsubscribe link and the one-click unsubscribe headers that Gmail and Yahoo require. Unsubscribed people stay in the list, marked as unsubscribed, and are never emailed again unless they resubscribe.

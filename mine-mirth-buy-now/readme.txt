=== Mine Mirth Buy Now & Price Alerts ===
Requires at least: 6.0
Requires PHP: 7.4
WC requires at least: 7.0
Stable tag: 1.0.4
License: GPLv2 or later

Adds two things to every product page, styled with the Mine Mirth theme (light and dark mode):

1. "Buy it now": sits inside the purchase box under "Add to cart". It adds the product with the chosen quantity (and options on variable products) and goes straight to checkout. Pre-order products show "Pre-order now".
2. "Get price alerts": a box under the purchase box. Visitors enter their email and are added to your existing newsletter subscribers (marked "price-alert"). When you change that product's price, they get an email.

== How price alerts work ==

* Any price change triggers it: the product edit screen, quick edit, bulk edit, a scheduled sale starting or ending, or an import that saves products.
* Price drop: "Price drop: [miner] is now $X", with the old and new price, the saving, a "Buy it now" button that goes straight to checkout, the product card and a "Get a quote" box.
* Price increase: "Heads up: the [miner] price went up", with a gentle nudge to order before it moves again.
* Every change is sent, however small (even $0.50), compared with the price each person last saw. Re-saving without a price change sends nothing.
* Alerts are sent while WordPress saves the price, reading prices and the send list straight from the database, so page caches and object caches (LiteSpeed, Redis, Memcached) can never hold them back.
* Optional short wait before sending (5, 15 or 60 minutes) so you can fix a typo first. If you put the old price back in that time, nothing is sent.
* Every alert has a "Stop alerts for this product" link and your newsletter's unsubscribe link.

With the Mine Mirth Mailer active, alerts use your newsletter design and sender settings, opens and clicks are tracked, and replies arrive in Mailer › Inbox. Without it, a simpler email in your site colours is sent.

== Cache purge ==

After every price-alert action (a sign-up, alerts sent, alerts stopped, settings saved) the plugin purges caches automatically: LiteSpeed Cache (page, CSS/JS and object cache), WP Rocket, W3 Total Cache, WP Super Cache, WP Fastest Cache, SiteGround, Breeze, Hummingbird, Nginx Helper, Autoptimize, and Comet Cache. (The object cache is not flushed separately, because that can throw away emails an SMTP plugin has queued; LiteSpeed's own purge-all handles its object cache.) Choose "all caches" (default) or "only that product's page" in the settings, or click "Purge all caches now".

== Email delivery ==

"Sent" in the log means your mail server accepted the email. The settings page now shows how every alert left the site (SMTP host, mailer or PHP mail()), the exact error for failures, and warnings for settings that silently stop email (no SMTP plugin, WP Mail SMTP "Do Not Send", FluentSMTP "Email Simulation"). Alerts are sent without open/click tracking by default, which keeps them out of Spam/Promotions. You can send a test to any address and get a copy of every alert.

== Settings: WooCommerce › Buy Now & Alerts ==

* Button labels and style (solid dark, blue outline or blue gradient).
* Whether "Buy it now" checks out only that product, or keeps the rest of the cart (default).
* Alert box texts, whether to show "X people are watching", alerts on drops and/or increases, and sending delay.
* Preview both emails, send a test to any address, see which products are watched and the last 100 alerts sent (times in Pakistan time by default; change under Delivery and time).

On a product's edit screen, a note under the price shows how many people will be alerted if you change it.

Emails go through WordPress mail, so keep an SMTP plugin (WP Mail SMTP or FluentSMTP) set up.

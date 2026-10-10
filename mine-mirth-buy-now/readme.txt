=== Mine Mirth Buy Now & Price Alerts ===
Requires at least: 6.0
Requires PHP: 7.4
WC requires at least: 7.0
Stable tag: 1.0.2
License: GPLv2 or later

Adds two things to every product page, styled with the Mine Mirth theme (light and dark mode):

1. "Buy it now": sits inside the purchase box under "Add to cart". It adds the product with the chosen quantity (and options on variable products) and goes straight to checkout. Pre-order products show "Pre-order now".
2. "Get price alerts": a box under the purchase box. Visitors enter their email and are added to your existing newsletter subscribers (marked "price-alert"). When you change that product's price, they get an email.

== How price alerts work ==

* Any price change triggers it: the product edit screen, quick edit, bulk edit, a scheduled sale starting or ending, or an import that saves products.
* Price drop: "Price drop: [miner] is now $X", with the old and new price, the saving, a "Buy it now" button that goes straight to checkout, the product card and a "Get a quote" box.
* Price increase: "Heads up: the [miner] price went up", with a gentle nudge to order before it moves again.
* Every change is sent, however small (even $0.50), compared with the price each person last saw. Re-saving without a price change sends nothing.
* Optional short wait before sending (5, 15 or 60 minutes) so you can fix a typo first. If you put the old price back in that time, nothing is sent.
* Every alert has a "Stop alerts for this product" link and your newsletter's unsubscribe link.

With the Mine Mirth Mailer active, alerts use your newsletter design and sender settings, opens and clicks are tracked, and replies arrive in Mailer › Inbox. Without it, a simpler email in your site colours is sent.

== Settings: WooCommerce › Buy Now & Alerts ==

* Button labels and style (solid dark, blue outline or blue gradient).
* Whether "Buy it now" checks out only that product, or keeps the rest of the cart (default).
* Alert box texts, whether to show "X people are watching", alerts on drops and/or increases, and sending delay.
* Preview both emails, send yourself a test, see which products are watched and the last 100 alerts sent.

On a product's edit screen, a note under the price shows how many people will be alerted if you change it.

Emails go through WordPress mail, so keep an SMTP plugin (WP Mail SMTP or FluentSMTP) set up.

=== Mine Mirth Subscriptions ===
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Sell your hosting plans as Stripe subscriptions. Plans you create in WordPress are created in Stripe at once, with their image, and show on your hosting plans page with a Subscribe button. You see every subscription, client and invoice in WordPress, can refund, add a discount or an extra charge (repairs, parts, fees), download branded PDF invoices, and your clients get Mine Mirth styled emails.

== Connect Stripe (about 2 minutes) ==

Where: wp-admin › Subscriptions › Settings › Connect Stripe.

1. Click "Test keys" on that page. It opens Stripe Dashboard › Developers › API keys in test mode.
2. Copy the Publishable key (pk_test_…) and the Secret key (sk_test_…, click "Reveal") into the two boxes.
3. Click "Save and connect". The plugin checks the keys and creates the webhook in Stripe for you. You see "Connected: your account name".
4. Once: Stripe Dashboard › Settings › Billing › Customer portal › Activate (the "Customer portal settings" button opens it). Clients use the portal from their emails to update their card or download receipts.
5. When you are ready for real payments, click "Live keys", paste the pk_live_… and sk_live_… keys, choose "Live mode" and click "Save and connect" again.

Test mode and live mode keep their data apart: switching the mode switches what you see. In test mode pay with the card 4242 4242 4242 4242, any future date and any CVC.

If "Save and connect" can not create the webhook (rare: a firewall or a security plugin), open "Set up the webhook by hand instead" under the keys. It shows the endpoint URL, the events to select and the API version; paste the signing secret (whsec_…) Stripe gives you and save.

Your site must use HTTPS for Stripe to send webhooks in live mode.

== Plans ==

Hosting Plans › edit a plan (or Add new):

* Plan image (right column): shown on the plan card, at Stripe checkout, in Stripe, in emails and on PDF invoices. Landscape, at least 1200 × 800, works best.
* "Stripe subscription" box: tick "Customers can subscribe to this plan online", enter the price and how often it bills (every 1 month, 3 months, 1 year…). Optional: a one-time setup fee on the first bill, a free trial, and "Price is per unit" so clients choose how many miners (up to a maximum).
* Click Update. The product and its price are created in Stripe immediately. The box then says "In Stripe" with a link to the product. If Stripe refuses something, the error is shown there.

Changing the price or billing period creates a new price in Stripe for new subscribers. Current subscribers keep the price they signed up for. Untick "Customers can subscribe" to stop selling a plan online. Moving a plan to the trash archives it in Stripe. Existing subscriptions keep running either way.

Plans that are not sold online keep the theme's normal "Request availability" button.

== Where plans appear ==

Subscriptions › Settings › Plans & checkout:

* Default: on your hosting plans page (/miner-hosting/), the plan list is replaced by the same cards with images, quantity and Subscribe buttons. Nothing else on the page changes.
* Or add the cards at the top of the page, or only where you place the shortcode [mm_subscription_plans].

Subscribe goes to Stripe's secure checkout. After paying, the client comes back to the plans page and sees their client number.

== Client numbers ==

Every client gets a number, MMC-1001, MMC-1002 and so on (change the prefix and next number in Settings › Plans & checkout). It is saved on the customer in Stripe too, shown in every list, email and invoice, and you can search by it. A returning client who subscribes again with the same email keeps their number.

== Managing subscriptions ==

Subscriptions › Overview: active subscriptions, monthly recurring revenue, money collected in the last 30 days, failed payments, renewals coming up and the latest invoices.

Subscriptions › Subscriptions: all subscriptions with plan image, client number, status, amount and next billing. Filter by status or search. Click one to open it:

* Refund: pick an invoice, leave the amount empty for a full refund or type part of it, and add a note for the client. The money goes back to their card through Stripe and they are emailed.
* Discount on the next billing: an amount ($25) or a percent (10%), with the text the client sees. It appears under "On the next billing" until it is billed; Remove takes it off.
* Extra charge: repairs, parts or any fee. "Add to the next billing" puts it on their next invoice; "Charge the card now" bills their saved card at once with its own invoice.
* Cancel at period end (and undo), Cancel now, Send billing reminder now, Re-send welcome email.
* Invoices with PDF download, Pay link for unpaid ones, Email receipt again.
* History of everything that happened.

Subscriptions › Customers: one row per client number, with what they have paid. The client page has their details, private notes, all subscriptions and invoices.

"Send a subscription link" (on the Subscriptions page) emails a client a ready-made checkout link for a plan.

== PDF invoices ==

Every invoice can be downloaded as a PDF in Mine Mirth branding: your logo, company details from Settings › Company & invoices, client number, plan image, lines, discounts, payments, refunds and balance. Receipts emailed to clients have the PDF attached.

== Emails ==

Sent automatically in the Mine Mirth design (Settings › Emails turns each on or off):

* Welcome when someone subscribes, with their client number.
* Billing reminder a few days before each renewal (3 by default).
* Receipt with the PDF after each payment.
* Payment failed, with a link to update the card.
* Refund, discount and extra charge notices.
* Cancellation.
* Copies to you of new subscriptions, failed payments and cancellations.

The emails are sent by your WordPress site. If emails do not arrive, use an SMTP plugin.

== Good to know ==

* Stripe stays the record of money: anything you do in the Stripe Dashboard (refunds, cancellations, new subscriptions) shows up here through the webhook. "Sync with Stripe" reloads everything; it also runs on its own every 12 hours.
* Subscriptions › Activity lists every event, email and error.
* Who can use it: administrators and shop managers.
* Deactivating the plugin keeps all data. Your subscriptions keep billing in Stripe.

== Changelog ==

= 1.0.0 =
* First release.

=== Mine Mirth Hosting Listings ===
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Turns your miner hosting page into a compact, shop-style grid of hosting listings, gives every hosting plan its own full details page (opened in a new tab), and lets you choose where each product's "Host this miner" button goes. A plugin only: your theme is not changed.

Works with the Mine Mirth 5 theme and the Mine Mirth Subscriptions plugin (prices and the Subscribe button come from it).

== After installing ==

1. Plugins › Add New › Upload Plugin › choose the zip › Install › Activate.
2. Open your hosting page (/miner-hosting/). It now shows the header and the listing grid.
3. Settings: Hosting Plans › Listings & page.

If a listing page shows "Page not found" right after activating, open Settings › Permalinks and click Save once.

== The hosting page ==

Hosting Plans › Listings & page › Hosting page. The plugin builds the whole page:

* A short header: small label, title, intro and an optional button. Nothing general about rates is shown; each listing shows its own rate.
* The listings as compact cards (image, availability, location, title, short summary, three key specs, monthly price, the listing's rate, "View details"), 2, 3 or 4 per row.
* A search box, location and availability filters, and sorting (recommended, price, newest).
* The first 12 listings, then a "Show more" button (change the number, or 0 for all).
* Your own content below the listings (an editor on the same settings page), optional.

Featured listings come first ("Highlight this plan" in the plan's details), then the Order number of each plan.

Untick "Build this whole page" to return to the page as the theme draws it. "Then also show the page's own content" adds the sections you built on the page itself under the listings (off by default, since those sections mention a general rate).

"Use these compact cards wherever the site lists hosting plans" also swaps the large plan cards on any other page that has a "Hosting plans" section or plan shortcode. Shortcode for any page: [mm_hosting_listings] (options: limit="6" toolbar="0" columns="4").

== Listing pages ==

Every published hosting plan has its own page, for example /miner-hosting/antminer-s21-xp-270t-hosting/. Clicking a card opens it in a new tab (Settings › "Open the full listing page in a new tab").

To add a listing: Hosting Plans › Add new.

* Title and the main text box: the title and the "Overview".
* Card summary: one or two sentences for the card (if empty, the start of the main text is used).
* Plan image (right column): the card image and the first image on the page. "More images" in the Listing page box adds a gallery.
* Listing page box: Key specs, one per line as "Label: value" (Hashrate: 270 TH/s). The first three show on the card. Optionally pick the Miner product: its page then shows "Need the miner too?", and that product's "Host this miner" button links to this listing.
* Hosting plan details (theme box): location, availability, rate, billing, minimum, cooling, setup, term, what's included and notes.
* Stripe subscription box (Subscriptions plugin): the price and Subscribe button with the quantity picker. Without a price there, the page shows "Request availability".

Click Publish or Update: the page is live at once. The notice and the Listing page box show its address with a link that opens it in a new tab.

Listing pages show: images, availability and location, price and rate, specs, the Subscribe box (or Request availability / fully booked), a help line ("Questions about this listing? Message us"), the overview, what's included, a details table, notes and other listings.

Settings › Listing pages: the web address part (default "miner-hosting"), the help line text and its link (a WhatsApp link such as https://wa.me/15035550100 works), and "Other listings" on or off.

== "Host this miner" on product pages ==

Settings › Host this miner button sets where it goes for every product:

* the hosting page (default), one hosting listing, or a custom link;
* "When a listing names a miner as its Miner product, that miner's button goes to the listing" (on by default);
* its title and small text (empty = the theme's), and opening in a new tab.

Each product can override it in its own "Host this miner button" box (right column of the product editor): use the default, a hosting listing, a custom link, or hide it on that product. The box shows where the button goes now.

To hide the box on every product, turn off "Hosting upsell box" in Appearance › Customize (product page).

== Deactivating ==

The hosting page goes back to the theme's version and listing pages stop being public. Your plans and settings are kept.

== Changelog ==

= 1.0.0 =
* First release.

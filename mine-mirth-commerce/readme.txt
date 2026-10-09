=== Mine Mirth Commerce ===
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Store fixes and features for minemirth.com. Works best with the Mine Mirth theme; WooCommerce is needed for everything except the page rebuilder.

== What it does ==

1. Product pages: the image gallery no longer stays fixed over the description while scrolling. The description tab opens first, and the brand shows above the title.
2. Page rebuilder (MM Commerce › Page rebuilder): reads /sitemap_index.xml (Yoast or Rank Math) or /wp-sitemap.xml, lists every page, and rebuilds each one as editable section shortcodes with your original text inside. Rebuilds are saved as drafts first. "Apply to live page" replaces the live page, and the old version stays in Revisions. Store pages, the homepage and the blog index are left alone.
3. Shop filters: category, price, brand (WooCommerce Brands, Perfect Brands, YITH Brands or a "Brand" attribute) and every product attribute, using their real names and labels. Also in stock and on sale, with removable chips. They appear in the shop sidebar automatically, or anywhere with [mmc_shop_filters].
4. Crypto payments to your own wallets (WooCommerce › Settings › Payments › Crypto): BTC, LTC, DOGE, ETH, USDT (ERC-20/TRC-20), USDC and SOL. Each order shows the exact amount, a QR code and the address, with the rate locked for 30 minutes. Bitcoin and Litecoin payments can be confirmed automatically from the public blockchain. Other coins are confirmed by you after the customer pastes their transaction ID. An optional crypto discount is available. No processor and no fee. Works in both the classic and the block checkout.
5. Subscriptions without the paid extension: on any simple product, tick "Subscription" and set the period, number of payments, sign-up fee and free trial. The first order starts the subscription. Each renewal creates an invoice emailed to the customer with a pay link. Customers see and cancel their subscriptions in My account › Subscriptions. Admins manage them in WooCommerce › Subscriptions.

== Section shortcodes ==

See MM Commerce › Page rebuilder for the full list: mmc_hero, mmc_section, mmc_split, mmc_features/mmc_feature, mmc_steps/mmc_step, mmc_stats/mmc_stat, mmc_faq/mmc_q, mmc_products, mmc_contact, mmc_legal, mmc_cta.

== Notes ==

* Renewals are pay-by-link. Cards are not charged automatically, because that needs a gateway with saved cards.
* Crypto prices come from CoinGecko's public API and are cached for 2 minutes. Automatic checks read mempool.space and litecoinspace.org. Neither service ever touches your funds.
* Double-check every wallet address. Coins sent to a wrong address cannot be recovered.

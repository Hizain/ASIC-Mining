=== Mine Mirth Shop Pro ===
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Shop page upgrades for Mine Mirth. Works with the Mine Mirth theme as installed, and needs WooCommerce.

== Filters ==

The panel appears at the top of the shop sidebar automatically. You can also place it anywhere with [minemirth_shop_filters].

* Availability: In Stock, Pre-Order and Out of Stock, each with its count. Pre-Order covers products set to "On backorder" (which the theme already labels Pre-order) and products marked by a pre-order plugin. Rename the three options in WooCommerce › Shop Pro.
* Categories: the full tree with subcategories you can open and close. Counts include products in subcategories.
* Price: a range slider with min/max boxes.
* Brand and every product attribute, using their real names. Short lists show as buttons; long lists get a search box. Values sort by real size, so 900 GH/s comes before 12 TH/s.
* Counts reflect the category you're viewing. Active filters show as removable chips, with a "Clear all" link.

If Mine Mirth Commerce is also active, its older filter panel is switched off automatically so only one panel shows.

== Best choice miners ==

Every few hours (6 by default), each miner with Hashrate and Power attributes is scored:
* 50% value: payback time at your reference power price, from live Bitcoin (SHA-256) and Litecoin + Dogecoin (Scrypt) network data and prices
* 20% efficiency against similar miners in your store
* 20% the 30-day price trend of the coin it mines
* 10% availability (In Stock ahead of Pre-Order; Out of Stock is never picked)

The top picks appear as "Best choices right now" above the products on the shop and category pages (page 1, no filters). Each shows its rank and a one-line reason, such as "Pays back in about 931 days at $0.08/kWh · 15.0 J/TH". Picked miners also get a "Best choice" label in the grid. Use [minemirth_best_miners count="4" title=""] to show the picks anywhere.

WooCommerce › Shop Pro shows the full ranking, lets you recalculate now, set the power price, number of picks and refresh interval, and exclude products.

Other algorithms (Kaspa, Alephium and so on) have no live profit data. They are ranked by efficiency and coin trend with a neutral value score, so they never beat a miner with measured payback on value alone.

== Notes ==

* Market data: CoinGecko (prices and trends), mempool.space (Bitcoin; your theme's cached data is used first), litecoinspace.org (Litecoin) and Blockchair (Dogecoin). Refreshes run in the background, so shoppers never wait for them.
* Estimates only, not guarantees.

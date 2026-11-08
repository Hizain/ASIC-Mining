=== Mine Mirth Miner Efficiency ===
Requires at least: 6.4
Requires PHP: 8.0
Requires Plugins: woocommerce
Stable tag: 1.0.1
License: GPLv2 or later

Shows each miner's efficiency from its own power draw and hashrate, in the right unit for its algorithm. A plugin only: your theme and your products are not changed. Settings: WooCommerce › Miner efficiency.

== What it fixes ==

The theme worked out efficiency as watts ÷ terahashes and always called it J/TH. That is right for Bitcoin (SHA-256) miners, but miners whose hashrate is in GH/s or MH/s came out 1,000 or 1,000,000 times too big. For example:

* Antminer L9 (16 GH/s, 3,360 W): 210,000 J/TH → 0.21 J/MH
* Goldshell AL-BOX (360 GH/s, 180 W): 500 J/TH → 0.5 J/GH
* Antminer Z15K (525 Ksol/s, 2,480 W): 4,723,809,523.8 J/TH → 4.72 J/kSol

Efficiency is now power ÷ hashrate in each algorithm's unit:

* SHA-256 (Bitcoin): J/TH
* Scrypt (Litecoin, Dogecoin): J/MH
* kHeavyHash (Kaspa): J/TH
* Blake3 (Alephium): J/GH
* Etchash: J/MH · Equihash: J/kSol · X11: J/GH
* Other: in the unit the hashrate is listed in

Change any of these in the settings.

== Where it shows ==

* The spec boxes on product pages (Hashrate, Power, Efficiency, Algorithm).
* The product's "Additional information" tab (can be turned off).
* Theme blocks that show efficiency, such as product promotions and product sections on pages.
* Shop cards, wherever the card shows an efficiency. Cards have room for three specs: a card that shows hashrate, power and algorithm keeps them, unless you turn on "Show efficiency on shop cards".

== Per product ==

WooCommerce › Miner efficiency lists every miner (any product with a hashrate) with:

* a tick box to switch the correction on or off for that product (unticked products show the theme's own figure);
* Hashrate and Power boxes: empty uses the product's attributes; fill one in to override it for efficiency only;
* Unit: the algorithm default, or a fixed unit for that product;
* Exact text: shown as typed, instead of the calculation (for example "13.5 J/TH");
* what the theme showed and what is shown now.

"Tick all" and "Untick all" work on the page. "Show all products" also lists products without a hashrate.

The same options are in an "Efficiency" box on each product's edit screen (right column), with a preview.

New products follow "Switch it on for new products automatically". Untick "Show corrected efficiency in the shop" to switch everything off at once; each product keeps its own setting for when you switch back on.

== Good to know ==

* Hashrate and power are read from the product's attributes (Hashrate, Hash Rate, Power, Power Consumption…), else from the product name ("525Ksol/s 2480W"), or from the values filled in here. Any spelling works: 525 Ksol/s, 525KSol/s, 1.16Ph/s, 200Th/s, 16 GH/s.
* Miners rated in Sol/s or kSol/s are Equihash (Zcash) and use J/kSol.
* If your site uses a page cache, clear it after updating so visitors see the new figures.
* Your product data is never changed: the corrected efficiency is added only when pages are shown.
* The interactive tools (miner comparison, calculators) do their own maths in the visitor's browser and are not changed by this plugin.

== Changelog ==

= 1.0.1 =
* Fixed: Equihash miners listed in Ksol/s (any capitalisation, e.g. Antminer Z15K) kept a huge J/TH figure. They now show J/kSol.
* Fixed: shop cards that show an efficiency now get the corrected figure without turning on the card setting.
* Hashrate and power are also read from the product name when the product has no attributes for them.

= 1.0.0 =
* First release.

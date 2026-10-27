=== Mine Mirth 5 ===
Contributors: minemirth
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 5.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Premium WooCommerce theme for minemirth.com: ASIC miners, miner hosting and mining education, with light and dark mode.

== What's new in 5.0 ==

Mine Mirth 5.0 rebuilds the whole website, not just the theme:

* **An 80-page site in one click.** Appearance › Site builder creates (or updates) 80 premium pages: home, about, contact, why us, FAQ, reviews, payment, shipping, warranty and returns; 8 service pages including hosting; 20 miner collections and brand pages that list your real stock; 13 tool pages; a Learn hub with 24 practical guides; a live market page, partners, privacy and terms. It also builds the main menu (with mega menus) and footer menus and sets the new homepage.
* **12 new live mining tools** built from today's market: profitability with a 24-month outlook, hashprice tracker with history, break-even electricity price, ASIC miner comparison (your store, ranked at your power price), ROI and payback scenarios, halving countdown and impact, difficulty adjustment estimator, hosting vs home, power and circuit planner, upgrade calculator, solo mining odds, and Litecoin/Dogecoin/Kaspa earnings. They use live price, difficulty and fees, your products and your hosting plans.
* **Premium section block.** Every page is made of the new "Mine Mirth section" block, with 22 layouts (hero, feature grid, text and visual, live stats, steps, FAQ, comparison table, miners from the shop, link cards, live network, tools, real reviews, hosting plans, contact, callout, brands, articles, newsletter, trust badges, rich text). Change any text, link, layout or background in the editor's sidebar. Live values such as {btc_price}, {hashprice}, {difficulty} and {halving_days} can be typed into any text.
* **Every page works on every device**, from 320px phones to wide desktops, in light and dark mode.

= Upgrading from 4.x =

1. Back up your site.
2. Appearance › Themes › Add New › Upload Theme, choose minemirth-5.0.0.zip and click "Replace active with uploaded". Your settings, logo, products, hosting plans and posts stay as they are.
3. Deactivate the old tool plugins: Mine Mirth Rig Optimizer, Mine Mirth Market Scope and Mine Mirth Tools (showcase). The new tools replace them.
4. Open Appearance › Site builder. Review the list (nothing changes until you click Build), keep the options ticked and click "Build the ticked pages".
5. Purge your cache (LiteSpeed Cache › Purge All) and view the site.

How the builder protects your work:

* Pages you already have at the same address (for example /about/ or /miner-hosting/) are updated in place, and their previous content is kept in Revisions.
* Your shipping, warranty, returns, privacy and terms pages are kept unless you tick them.
* WooCommerce pages (shop, cart, checkout, account) and your blog page are never touched.
* After the first build, pages you edit are left alone on later builds unless you tick them.
* Old tool pages are moved to drafts and their addresses redirect (301) to the matching new tool. The /free-calculators/ address and the Bitcoin mining calculator address stay the same.

== Description ==

Mine Mirth is a full rebuild of the minemirth.com theme. Its brand colour is #35a8f6 and it includes:

* Light and dark mode. Visitors can switch with an animated toggle, the site can follow their device setting, and the page never flashes the wrong theme while loading.
* An animated hero with a rotating hash-network globe, an isometric ASIC rig, floating live-data cards, a rotating headline word and a particle background. You can choose each effect or turn it off, and the theme respects "reduce motion".
* Live Bitcoin network data (price, hashrate, difficulty, block height, next adjustment, halving countdown). The server fetches and caches it, and the browser fetches it directly if your host blocks outbound requests.
* A profitability calculator with miner presets taken from your products, plus a hosting cost calculator.
* Hosting plans (compatible with the plans you already created in Nova Pro) and a "Miner Hosting" page template.
* In-content blog promotions that place product cards, the calculator, a hosting banner, a newsletter form or a custom promo inside articles, after a set number of paragraphs. The same promo never appears twice on one page. A "continue reading" cue brings readers back to the article, and an optional slide-in, sidebar promo and end-of-article call to action are also included.
* A premium WooCommerce experience: shop toolbar and filter drawer, quick view, AJAX add to cart, a slide-out mini cart with a free-shipping bar, a sticky add-to-cart bar, product spec grids, a distraction-free checkout with progress steps and trust badges, a redesigned order confirmation, and a customer account dashboard with stats, recent orders and quick links.
* A newsletter with a built-in subscriber list (Promotions › Subscribers) and CSV export, or send signups to Mailchimp, ConvertKit or a form plugin instead.
* SEO helpers: JSON-LD for Organization, WebSite, Article, FAQ and Breadcrumbs, a table of contents, reading time, /llms.txt and clean semantic markup.

= Customization =

Everything is in Appearance › Customize, grouped into seven "Mine Mirth" panels (over 400 settings). Most changes preview live.

1. Design System: style presets, brand colours, light and dark palettes, default mode, fonts, sizes, radius, container width, animation.
2. Header: announcement bar, sticky or transparent header, logo (including a dark-mode logo), icons, call-to-action button, search.
3. Homepage: drag to reorder or hide 15 sections, and edit every heading, text, button, image and item in each one.
4. Blog & Promotions: grid style, cards, article layout, table of contents, progress bar, share links, related posts, in-content promo frequency and type.
5. Shop, Checkout & Account: columns, card style, badges, quick view, sticky add-to-cart, free-shipping goal, checkout focus mode, trust copy, order confirmation, account links.
6. Footer: call to action, columns, contact details, social links, payment badges, disclaimer.
7. Pages, Data & Advanced: page headers, 404, hosting page, calculator defaults, market data, newsletter, performance and SEO.

Appearance › Mine Mirth has a setup checklist, shortcuts into each panel, one-click starter pages, and settings export, import and reset.

== Installation ==

1. Back up your site.
2. Go to Appearance › Themes › Add New › Upload Theme and upload minemirth.zip.
3. Activate "Mine Mirth". Your custom logo and menu locations carry over from Nova Pro automatically.
4. Open Appearance › Mine Mirth and follow the checklist: set menus, create any missing pages (Hosting, Calculator, Contact), and choose a homepage.
WooCommerce is optional. The shop features turn on automatically when it is active.

== Moving from Nova Pro ==

* Your hosting plans (post type mm_hosting_plan) work as they are.
* The old shortcodes still work: [mine_mirth_hosting_plans], [mine_mirth_hosting_calculator] and [mine_mirth_market_snapshot].
* Pages using the old "Miner Hosting" template keep using it, because the template path is the same.
* If the old "Mine Mirth Core" plugin is still active, the theme shows a notice. Everything the plugin did is now part of the theme, so you can deactivate it once you have checked your plans.

== Promotions in articles ==

Promotions are added automatically according to Customize › Blog & Promotions. To create your own, go to Promotions › Add New. Choose a type, where it can appear (in the article, sidebar, end of article or slide-in), which categories it targets, its priority, devices and optional start and end dates. Each article also has a "Mine Mirth promotions" box where you can turn promos off for that article or choose which ones it uses.

In the block editor, add the "Mine Mirth promo" block anywhere in a post.

== Shortcodes ==

[mm_tool id="profit"]                        One live tool. Ids: profit, hashprice, breakeven, compare, roi, halving, difficulty, hosting, power, upgrade, solo, altcoin.
[mm_tools_grid limit="6" exclude="" group=""]  Tool cards (groups: profit, market, planning).
[mm_market]                                  Live Bitcoin network snapshot.
[mm_calculator variant="full|compact" title=""]  Profitability calculator.
[mm_hosting_calculator]                      Hosting cost calculator.
[mm_hosting_plans]                           Hosting plan cards.
[mm_newsletter]                              Newsletter signup form.
[mm_promo id="123"]                          Show a saved promotion.
[mm_product id="45"] or [mm_product ids="45,46,47" text="Our picks"]   Product card or product row.
[mm_cta title="" text="" button="" url="" icon="server" label=""]       Banner call to action.

== Page templates ==

Premium sections (full-width pages made of Mine Mirth sections), Guide (table of contents, reading time, related guides), Mining Tool (shows the page's tool with its guide below), Miner Hosting, Mining Calculator, Contact, Full width, Page with sidebar, Landing (canvas for page builders).

== Menus and widgets ==

Menus: Primary, Mobile, Footer columns 1–3, Footer bottom. In a primary menu item, add the CSS class "mm-mega" to show its sub-menu as a mega menu, or "icon-server" (any icon name) to show an icon next to it.
Widget areas: Blog sidebar, Shop sidebar, Page sidebar.

== Notes ==

* Market data comes from the public CoinGecko and mempool.space APIs. It is cached for the interval you set (2 minutes by default) and the last good value is kept if a request fails. You can turn it off in Customize › Pages, Data & Advanced › Live market data.
* Calculator results are estimates. The disclaimer text is editable in the Customizer.
* The theme is translation-ready (text domain: minemirth).

== Credits ==

* Fonts: Sora, Inter and JetBrains Mono (SIL Open Font License), served by Google Fonts. You can choose other fonts or system fonts in the Customizer.
* Icons and illustrations were drawn for this theme and are licensed GPLv2 or later.

== Changelog ==

= 5.0.0 =
* New: Site builder (Appearance › Site builder) with 80 premium pages, mega menu, footer menus and new homepage.
* New: 12 live mining tools with shared market data engine, daily hashprice and difficulty history, altcoin data and store catalog.
* New: "Mine Mirth section" block with 22 editable layouts and live value tokens.
* New: Premium sections, Guide and Mining Tool page templates.
* New: SEO title and description for every built page (also written to Yoast, Rank Math, AIOSEO or SEOPress when active).
* Changed: the Mining Calculator template and [mm_calculator] now show the new profitability tool. Old tool shortcodes fall back to the new tools when their plugins are switched off.
* Customize › Mine Mirth · Pages, Data & Advanced › Mining tools: tools address, and optional Litecoin, Dogecoin and Kaspa network hashrate overrides.

= 4.0.2 =
* New mm_logo_html filter so plugins can set separate header and footer logos per light and dark mode.

= 4.0.1 =
* Product gallery no longer covers the description while scrolling.

= 4.0.0 =
* A complete rebuild, replacing Nova Pro 3.x.

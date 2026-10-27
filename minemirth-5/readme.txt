=== Mine Mirth 5 ===
Contributors: minemirth
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 5.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Premium WooCommerce theme for minemirth.com: ASIC miners, miner hosting and mining education, with light and dark mode.

== What's new in 5.1 ==

* **Installs next to your current theme.** Mine Mirth 5 has its own folder (minemirth-5) and name, so you can upload it without replacing the theme your visitors see. It copies your current settings on first use.
* **Test it privately first** with the Mine Mirth Launch plugin: only you see Mine Mirth 5, on the site, in wp-admin and in the editor. The site builder creates new pages as private and keeps changes to existing pages as drafts until you click "Go live".
* **Every existing address keeps working.** Mine Mirth Launch remembers every page, post, product, category and tag address, and sends old or changed addresses to the right place with a 301 redirect. Anything it cannot match appears in a 404 log with a suggested page.
* **Animations are back on the homepage**, and premium motion runs across the whole site: the animated hero with the 3D hash sphere or mining rig, live floating cards and a rotating coin word; the live ticker; crypto vector shapes (coins, blocks, a blockchain that confirms new blocks, circuit traces, network nodes) behind sections, page headers, articles, the shop, product pages and the footer; typed eyebrows, word-by-word headings, drawn step lines, counters and a cursor spotlight. Shapes are pure CSS and pause when off screen, so pages stay fast.
* **A live guide talks to visitors.** A small assistant explains what today's numbers mean for the page they are reading: what this miner earns per day and its break-even power price on a product page, hashprice and the next difficulty change on articles, the halving countdown, and a link to the right tool. It never covers the opening screen, stays quiet on cart, checkout and account pages, and can be turned off in Customize › Animation & effects.
* **SEO on every page.** Each of the 80 pages has a focus keyword, written into its SEO title, meta description, H1, opening paragraph and headings, and passed to Yoast, Rank Math or SEOPress. Every page has at least 400 words (guides at least 800) with a keyword density between 0.5% and 2.5%. The Site builder shows the keyword, density and word count of each page.
* **No long dashes** in the page content or the theme's own text; number ranges read "200 to 240 V".

== Install and test before going live ==

1. Back up your site.
2. Plugins › Add New › Upload Plugin: install and activate mine-mirth-launch-1.0.0.zip. It immediately remembers every address your site has today.
3. Appearance › Themes › Add New › Upload Theme: install minemirth-5-5.1.0.zip. Do not activate it. It appears as "Mine Mirth 5" next to your current theme.
4. Open Launch (in the admin menu) and click "Start test drive". From now on only you see Mine Mirth 5. Visitors keep your current theme and pages.
5. Open Appearance › Site builder and click "Build the ticked pages". New pages are created as private, and changes to existing pages are saved as drafts inside them, visible only to you.
6. Browse the site, edit any page in the block editor and check everything on your phone too. To compare with the live site, open it in a private window.
7. When you are happy, click "Go live" at the top of the Site builder. It applies the drafts (the old content stays in Revisions), publishes the new pages, sets the homepage, moves the old tool pages to drafts with redirects, and activates Mine Mirth 5 for everyone.
8. Purge your cache (LiteSpeed Cache › Purge All). For the first week, check Launch › 404 log and fix anything left with one click.

Changed your mind? Click "Mine Mirth 5: test drive ON" in the admin bar to turn it off. Nothing changed for visitors.

After going live, deactivate the old tool plugins (Mine Mirth Rig Optimizer, Mine Mirth Market Scope and Mine Mirth Tools showcase); the new tools replace them. Keep Mine Mirth Launch active: it keeps the old addresses redirecting.

== What's new in 5.0 ==

* **An 80-page site in one click.** Appearance › Site builder creates (or updates) 80 premium pages: home, about, contact, why us, FAQ, reviews, payment, shipping, warranty and returns; 8 service pages including hosting; 20 miner collections and brand pages that list your real stock; 13 tool pages; a Learn hub with 24 practical guides; a live market page, partners, privacy and terms. It also builds the main menu (with mega menus) and footer menus and sets the new homepage.
* **12 live mining tools** built from today's market: profitability with a 24-month outlook, hashprice tracker with history, break-even electricity price, ASIC miner comparison (your store, ranked at your power price), ROI and payback scenarios, halving countdown and impact, difficulty adjustment estimator, hosting vs home, power and circuit planner, upgrade calculator, solo mining odds, and Litecoin, Dogecoin and Kaspa earnings.
* **Premium section block.** Every page is made of the "Mine Mirth section" block, with 24 layouts including the animated hero and the live ticker. Change any text, link, layout, background or animated shapes in the editor's sidebar. Live values such as {btc_price}, {hashprice}, {difficulty} and {halving_days} can be typed into any text.
* **Every page works on every device**, from 320px phones to wide desktops, in light and dark mode.

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
2. To test first, follow "Install and test before going live" above. To switch straight away, go to Appearance › Themes › Add New › Upload Theme, upload minemirth-5-5.1.0.zip and activate "Mine Mirth 5".
3. Your settings, logo and menu locations are copied from the current Mine Mirth theme automatically.
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

Menus: Primary, Mobile, Footer columns 1 to 3, Footer bottom. In a primary menu item, add the CSS class "mm-mega" to show its sub-menu as a mega menu, or "icon-server" (any icon name) to show an icon next to it.
Widget areas: Blog sidebar, Shop sidebar, Page sidebar.

== Notes ==

* Market data comes from the public CoinGecko and mempool.space APIs. It is cached for the interval you set (2 minutes by default) and the last good value is kept if a request fails. You can turn it off in Customize › Pages, Data & Advanced › Live market data.
* Calculator results are estimates. The disclaimer text is editable in the Customizer.
* The theme is translation-ready (text domain: minemirth).

== Credits ==

* Fonts: Sora, Inter and JetBrains Mono (SIL Open Font License), served by Google Fonts. You can choose other fonts or system fonts in the Customizer.
* Icons and illustrations were drawn for this theme and are licensed GPLv2 or later.

== Changelog ==

= 5.1.0 =
* New: separate theme folder and name (Mine Mirth 5) so it installs next to the current theme; settings are copied on first use.
* New: test mode for the site builder with "Go live", working with the Mine Mirth Launch plugin (test drive, address memory, 301 redirects, 404 log).
* New: "Animated hero" and "Live ticker" sections; the homepage opens with them again.
* New: crypto vector shapes and premium motion across the site, and the live guide bubble. New Customizer options: Crypto vector shapes, Live guide bubble, Guide appears after.
* New: focus keywords, keyword placement and density on all 80 pages, with a keyword, density and word count column in the Site builder.
* Changed: no long dashes in content and theme text.
* Fixed: a spinning orbit ring could make phones scroll sideways.

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

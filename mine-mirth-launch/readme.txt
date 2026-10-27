=== Mine Mirth Launch ===
Contributors: minemirth
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Test Mine Mirth 5 privately, then launch it with every existing address redirected to the right place.

== Description ==

* **Test drive.** Chosen administrators see Mine Mirth 5 on the site, in wp-admin and in the block editor, while every visitor keeps the current theme. Test-drive pages are never stored by page caches.
* **Build privately.** With Mine Mirth 5's Site builder, new pages are created as private pages and changes to existing pages are kept as drafts that only you see, until you click "Go live".
* **Address memory.** On activation, every day and just before launch, the plugin remembers the address of every page, post, product, category, tag, archive and author, and it records an address the moment it is about to change or disappear.
* **Smart 301 redirects.** When a visitor or search engine asks for an address that no longer exists, the plugin sends it to the best match: your own rules first, then the new page that replaced it, the page or product at its new address, its parent, category or section, a page with the same name, a page on the same topic, or the closest section of the site. Query strings are kept. Redirects only apply to addresses that would otherwise show a 404, so they never override a real page.
* **404 log** with a suggested page for every address that could not be matched, and one-click rules.
* **URL check**: type any old address and see where it goes.

== Installation ==

1. Plugins › Add New › Upload Plugin, choose mine-mirth-launch-1.0.0.zip, install and activate.
2. Open Launch in the admin menu and follow the steps on screen.

Keep the plugin active after launch so old addresses keep redirecting.

== Changelog ==

= 1.0.0 =
* First release: test drive, private building with Go live, address memory, smart 301 redirects, manual rules, URL check and 404 log.

=== Mine Mirth Guide Page Fix ===
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Fixes the guide pages (Learn › any guide, for example /learn/bitcoin-mining-for-beginners/) on the Mine Mirth 5 theme.

== The problem ==

On guide pages the article, the "Related guides" cards and the "Run the numbers on your setup" card were squeezed into narrow columns and covered each other.

The theme's Guide page template and the floating live-guide bubble used the same class name ("mm-guide"), so the bubble's styles (fixed to the corner of the screen, 380 pixels wide) were applied to the whole page.

== What the plugin does ==

On pages that use the Guide template it renames the template's classes to "mm-gpage" and gives them their intended styles. Nothing else changes: other pages, the theme files, the live-guide bubble and your other plugins are not touched.

== Install ==

1. Plugins › Add New › Upload Plugin, choose mine-mirth-guide-fix-1.0.0.zip, Install, Activate.
2. If you use a caching plugin or host cache, clear the cache so visitors get the fixed pages.

There are no settings. Deactivating the plugin brings the old behaviour back.

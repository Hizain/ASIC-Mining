=== Mine Mirth Hero Spacing ===
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Removes the large empty band between the header and the hero on the Mine Mirth 5 theme, on every page, on desktop and phones.

== The problem ==

* Homepage: the hero was stretched to 92% of the screen height with its content centred, and its top padding added the header's height again, so there was a band of 140 to 190 pixels of empty space under the header on desktop and about 180 pixels on phones.
* On phones, a theme rule added about 56 pixels of section padding above the homepage hero and around the live ticker, which are meant to have none.
* Other pages each used a different gap (24 to 96 pixels).

== What the plugin does ==

Every hero starts the same short distance below the header: about 48 pixels on desktop and 28 pixels on phones (it scales smoothly in between). The homepage hero is sized to its content, so the live ticker shows on the first screen. If you turn on the transparent header over the homepage hero, the hero still makes room for the header.

The hero's design and animations do not change.

== Install ==

1. Plugins › Add New › Upload Plugin, choose mine-mirth-hero-spacing-1.0.0.zip, Install, Activate.
2. If you use a caching plugin or host cache, clear it.

There are no settings. It works together with Mine Mirth Guide Page Fix and Mine Mirth Menu Fix.

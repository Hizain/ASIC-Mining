=== Mine Mirth Menu Fix ===
Requires at least: 6.4
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

Fixes the desktop header menus (Shop, Tools, Learn, Company) on the Mine Mirth 5 theme.

== The problem ==

On a desktop or laptop, moving the mouse from a menu name towards a link in its dropdown often switched to another menu, closed the menu, or clicked a link you could not see.

* Closed dropdowns only fade to invisible. Their links still caught the mouse, so passing over the area of another menu opened that menu, and a click could land on a hidden link.
* A dropdown that is fading out kept catching the mouse for a moment.
* A small dead gap between a menu name and its dropdown closed the menu when you moved slowly.
* Crossing a neighbouring menu name on the way to a link switched menus at once.

== What the plugin does ==

* Closed dropdowns no longer react to the mouse.
* The menu names fill the full header height, so there is no gap.
* Hover intent: a menu opens at once, stays open while you move down into it (even crossing other menu names), and another menu only opens when you rest on its name.

The look of the menus does not change. Keyboard use (Tab) works as before, and phones and tablets keep the normal mobile menu.

== Install ==

1. Plugins › Add New › Upload Plugin, choose mine-mirth-menu-fix-1.0.0.zip, Install, Activate.
2. If you use a caching plugin or host cache, clear it.

There are no settings. It works together with Mine Mirth Guide Page Fix and your other plugins.

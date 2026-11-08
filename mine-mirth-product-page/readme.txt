=== Mine Mirth Product Page ===
Requires at least: 6.4
Requires PHP: 8.0
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later

A fast product image gallery and a readable product description for WooCommerce product pages. A plugin only: your theme is not changed. Settings: WooCommerce › Product page.

== What it fixes ==

Product pages sat still and then jumped: WooCommerce's slider script rebuilt the gallery after the page had loaded, hiding the photo for a moment and adding the thumbnails late. It also downloaded the full-size original photo straight away for the zoom.

This plugin draws the gallery at its final size on the server, so the photo and thumbnails are in place from the first moment and nothing moves. Only the main photo loads first; other photos load as they are needed, and full-size originals only when someone zooms or opens full screen. WooCommerce's slider, zoom and lightbox scripts are no longer loaded on product pages.

== Image gallery ==

* The main image, with the thumbnail carousel below it (or on the left). Click a thumbnail or the arrows, swipe on phones, or use the keyboard arrows.
* Hover to zoom (desktop) and click to view full screen (swipe, arrows, Esc to close).
* Variable products: choosing an option shows that option's image.
* Images come from the product's "Product image" and "Product gallery" boxes, in that order.

Settings (WooCommerce › Product page › Image gallery):
* Image shape: square, landscape, wide or portrait; show the whole image or fill the frame.
* Background (soft blue glow, plain card, white, none) and corners.
* Thumbnails below, left or hidden; how many are visible at once; thumbnails or dots on phones.
* Arrows on hover, always or never; image counter; loop.
* Zoom on or off and how much; full-screen view on or off; autoplay (off by default).
* Untick "Use this gallery" to go back to WooCommerce's gallery.

== Description ==

The Description tab gets one consistent design, whatever was pasted into it:

* readable text size, line spacing and line length;
* one heading scale (huge and tiny headings now match), with a blue bar, an underline or plain;
* check-mark bullets and numbered circles;
* spec-sheet tables (striped rows, labels in grey), scrolling sideways on phones;
* tips and quotes as highlighted notes;
* fonts, sizes and colours pasted from other websites are ignored (bold, links, lists, tables and alignment are kept);
* the biggest headings (H1) shown at section size: better for Google too, as the product name stays the only H1;
* an "On this page" bar with links to the sections when there are 3 or more;
* optionally, long descriptions shortened with a "Show the full description" button;
* optionally, a different tab name (for example "Overview").

Your product texts are not changed: only how they are shown. Untick "Use the readable description design" to go back.

"Reset to recommended" restores every setting.

== Changelog ==

= 1.0.0 =
* First release.

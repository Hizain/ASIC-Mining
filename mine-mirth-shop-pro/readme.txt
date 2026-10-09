=== Mine Mirth Shop Pro ===
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.2.0
License: GPLv2 or later

Shop filters for Mine Mirth. Works with the Mine Mirth theme as installed, and needs WooCommerce.

== Filters ==

The panel appears at the top of the shop sidebar automatically. You can also place it anywhere with [minemirth_shop_filters].

* Availability: In Stock, Pre-Order and Out of Stock, each with its count. Pre-Order covers products set to "On backorder" (which the theme already labels Pre-order) and products marked by a pre-order plugin. Rename the three options in WooCommerce › Shop Pro.
* Categories: the full tree with subcategories you can open and close. Counts include products in subcategories.
* Price: a range slider with min/max boxes.
* Every product attribute (Products › Attributes), using their real names. WooCommerce's separate Brands list is not shown, so a "Brand" attribute appears once. Short lists show as buttons; long lists get a search box. Values sort by real size, so 900 GH/s comes before 12 TH/s.
* Counts reflect the category you're viewing. Active filters show as removable chips, with a "Clear all" link.

If Mine Mirth Commerce is also active, its older filter panel is switched off automatically so only one panel shows.

== Changelog ==

= 1.2.0 =
* Category counts match what shoppers can see: sold-out products are not counted when the store hides them.
* The theme's category pills above the products show the same categories and counts as the filter panel, including the default category.

= 1.1.0 =
* Removed the "Best choice" feature and its settings.
* Filters show product attributes only, no WooCommerce Brands list, so there is one Brand heading.

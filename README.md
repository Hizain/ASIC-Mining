# Mine Mirth theme

The WordPress and WooCommerce theme for [minemirth.com](https://minemirth.com). Mine Mirth 5 (folder `minemirth-5`) installs next to the current Mine Mirth theme, so it can be tested privately before it goes live.

- Brand colour `#35a8f6`, with light and dark mode (visitor toggle, follows the device setting, no flash on load)
- An 80-page site builder with a private test mode and "Go live", 12 live mining tools and an editable premium section block
- An animated hero, a live Bitcoin network ticker, crypto vector shapes and a live guide that explains today's numbers
- A focus keyword, keyword placement and density on every builder page
- Premium WooCommerce pages: shop, quick view, mini cart, focused checkout, order confirmation and customer account
- 400+ Customizer settings in seven panels, most with live preview

The Mine Mirth Launch plugin (`mine-mirth-launch/`) runs the test drive and keeps every existing address working with 301 redirects after launch.

The full feature list, install steps and shortcodes are in [`minemirth-5/readme.txt`](minemirth-5/readme.txt).

## Layout

```
minemirth-5/               the theme (upload this folder, zipped)
  inc/options.php          single schema for every Customizer setting
  inc/customizer/          controls, sanitising, live preview wiring
  inc/modules/             market data, calculator, hosting, promos, newsletter, schema
  inc/sections/            the "Mine Mirth section" block and its layouts
  inc/builder/             site builder, test mode and the content of the 80 pages
  inc/motion.php           crypto shapes and the live guide (assets/js/motion.js)
  inc/woocommerce/         store hooks and helpers
  woocommerce/             template overrides (checkout, thank-you, account, login)
  template-parts/home/     homepage sections (also used by the animated hero and ticker)
  assets/css, assets/js    no build step; plain CSS custom properties and vanilla JS
mine-mirth-launch/         test drive, address memory, redirects and 404 log
bin/build-zip.sh           builds dist/minemirth-5-<version>.zip and the plugin zips
```

## Build

```sh
bin/build-zip.sh
```

Install the Launch plugin, then upload the theme zip in **Appearance › Themes › Add New › Upload Theme** without activating it, and follow the steps in the Launch screen. It needs WordPress 6.4+ and PHP 8.0+. WooCommerce is optional.

## Adding a setting

Add an entry to `mm_options_schema()` in `inc/options.php` with a `section`, `type`, `label` and `default`, then read it with `mm_opt( 'key' )`. Registration, sanitising, export, import and reset all come from the schema. For an instant preview, either give it `'transport' => 'postMessage'` and a `css` variable (`'css' => array( 'var' => '--mm-x' )`), or put it in a section that defines a `partial`.

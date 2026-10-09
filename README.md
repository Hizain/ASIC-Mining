# Mine Mirth theme

The WordPress and WooCommerce theme for [minemirth.com](https://minemirth.com). Version 4.0 is a full rebuild that replaces Nova Pro 3.x.

- Brand colour `#35a8f6`, with light and dark mode (visitor toggle, follows the device setting, no flash on load)
- An animated hero, a live Bitcoin network ticker, and profitability and hosting calculators
- In-content blog promotions with de-duplication and a "continue reading" cue
- Premium WooCommerce pages: shop, quick view, mini cart, focused checkout, order confirmation and customer account
- 430+ Customizer settings in seven panels, most with live preview

The full feature list, setup steps and shortcodes are in [`minemirth/readme.txt`](minemirth/readme.txt).

## Layout

```
minemirth/                 the theme (upload this folder, zipped)
  inc/options.php          single schema for every Customizer setting
  inc/customizer/          controls, sanitising, live preview wiring
  inc/modules/             market data, calculator, hosting, promos, newsletter, schema
  inc/woocommerce/         store hooks and helpers
  woocommerce/             template overrides (checkout, thank-you, account, login)
  template-parts/home/     the 15 homepage sections
  assets/css, assets/js    no build step; plain CSS custom properties and vanilla JS
bin/build-zip.sh           builds dist/minemirth-<version>.zip
```

## Build

```sh
bin/build-zip.sh
```

Upload the zip in **Appearance › Themes › Add New › Upload Theme**. It needs WordPress 6.4+ and PHP 8.0+. WooCommerce is optional.

## Adding a setting

Add an entry to `mm_options_schema()` in `inc/options.php` with a `section`, `type`, `label` and `default`, then read it with `mm_opt( 'key' )`. Registration, sanitising, export, import and reset all come from the schema. For an instant preview, either give it `'transport' => 'postMessage'` and a `css` variable (`'css' => array( 'var' => '--mm-x' )`), or put it in a section that defines a `partial`.

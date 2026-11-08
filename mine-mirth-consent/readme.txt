=== Mine Mirth Cookie Consent ===
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

A premium cookie banner for first-time visitors: "Accept" (highlighted) or "Deny". It does not just show a notice: it controls what tracking runs. Settings: Settings › Cookie consent.

== What visitors see ==

* First visit: a card at the bottom of the screen in the site's style (light or dark), with the title, a short explanation, a link to your privacy policy, and two equal-size buttons: Accept (highlighted, the theme's blue gradient) and Deny.
* Their choice is remembered (6 months by default), so the banner does not come back on every page or visit.
* A small cookie button (bottom left) lets them change their mind at any time. You can also add a "Cookie settings" link anywhere with [mm_cookie_settings], or a menu link to #mm-cookie-settings (for example in the footer).
* On phones it becomes a bottom sheet with two big buttons. Keyboard and screen-reader friendly: the banner is the first thing reached with Tab.

== What the choice controls ==

* Google Consent Mode v2: Google Analytics, Ads and Tag Manager are told "denied" before anything loads, and "granted" only after Accept. After Deny they keep only anonymous, cookieless measurement.
* Scripts to load after Accept (Tracking tab): paste tracking codes such as Meta Pixel, Hotjar or a Google Analytics snippet there. They run only for visitors who accept. Remove them from anywhere else on the site, or they still run for everyone.
* WP Consent API: plugins that follow it (for example Google Site Kit) read the choice. Install the free "WP Consent API" plugin to use this.
* Global Privacy Control: browsers that send "do not sell or share my data" are treated as Deny without asking (required in California).
* Changing from Accept to Deny removes common analytics and ad cookies (Google, Meta, Microsoft, HubSpot, Hotjar) and reloads the page so those trackers stop.
* Essential cookies (cart, login, checkout) always work: the shop cannot run without them.

For developers and Google Tag Manager: the choice is on <html data-mm-consent="accept|deny|none">, in mmConsent.get(), in the "mmcc:consent" event, and pushed to the dataLayer as the event "mm_consent_update".

== Settings ==

* Banner: wording (title, message, button labels, privacy link), position (card bottom left, wide card bottom centre, full-width bar), light/dark or follow the site, button colour, delay before it appears, the cookie button, hide for logged-in administrators. A live preview is at the top, and "See it on the site" opens your homepage with the banner showing.
* Tracking: Consent Mode, scripts after Accept, Global Privacy Control, how long the choice is remembered, and "Save and ask everyone again" (after adding a new tracking tool or changing your policy).
* Consent records: accepts, denies and the acceptance rate over the last 30 days, the latest 100 choices, and a CSV download of all records.

== Privacy ==

* The choice is stored in a first-party cookie, "mm_consent" (choice, consent version, date).
* Each record holds the date, the choice, the consent version, the page and an anonymous visitor code: a one-way hash of the IP address that changes every month and cannot be turned back into the address. No IP addresses or personal data are stored. Records are deleted after 12 months.
* Settings › Privacy › Policy guide suggests a paragraph about this banner for your privacy policy.

== Good to know ==

* Works with page caching: the banner is in every page and only appears for visitors without a stored choice, so returning visitors never see it flash.
* If you use the Mine Mirth Ticker at the bottom of the screen, the banner sits just above it.

== Changelog ==

= 1.0.0 =
* First release.

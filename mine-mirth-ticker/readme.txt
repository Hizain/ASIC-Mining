=== Mine Mirth Ticker ===
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

A scrolling announcement bar (right to left) with the live Bitcoin price, the hashprice of proof-of-work coins one by one, and your own messages. A plugin only: your theme is not changed. Settings: Appearance › Ticker bar.

== What it shows ==

In your order, then it starts again:

1. Bitcoin live price with its 24-hour change (▲ green / ▼ red).
2. Hashprices, one coin after another: what one unit of hashrate earns per day, in each coin's usual unit, for example
   Bitcoin $/PH/day · Litecoin and Dogecoin $/GH/day · Kaspa and Alephium $/TH/day · Zcash $/kSol/day · Monero $/kH/day.
   Thirteen major coins are on at the start (Bitcoin, Bitcoin Cash, Litecoin, Dogecoin, Kaspa, Alephium, Ethereum Classic, Zcash, Dash, Kadena, Handshake, Nervos, Monero). Every proof-of-work coin in the WhatToMine feed (about 70, ASIC and GPU) is listed in the settings to tick on or off; new coins appear there by themselves.
3. Your message: "☀️ Install Solar, Wind or Hydro Energy For Long Time Gains. Consult with us!!!", highlighted, with "Consult with us!!!" linking to your contact page.

Hashprice = block reward × blocks per day ÷ network hashrate × coin price.

== Settings (Appearance › Ticker bar) ==

A live preview of the bar is at the top of the page.

* Bar: on or off, on phones, hide on cart and checkout, let visitors close it.
* Where: top of the page instead of the theme's announcement bar (default), top of the page above it, or fixed at the bottom of the screen; every page or the homepage only. Shortcode [mm_ticker] puts it inside any page.
* Movement: speed, and pause while the mouse is over it. Visitors who turned off animations on their device see it still and can scroll it by hand.
* What it shows: tick items on or off, reorder them with the arrows, remove them, add as many messages as you like (icon, text, link text, link, highlighted or plain). The Bitcoin price and hashprices can be added again too.
* Labels: "Bitcoin" and "{name} hashprice" ({tag} for the ticker), 24-hour change on or off, the symbol between items, and repeating messages after every few coins.
* Coins: tick, order, rename and choose the unit of each coin. The current hashprice is shown next to each.
* Currency (USD, EUR, GBP, CAD, AUD, or the theme's) and how often prices update.
* Look: compact, standard or large; colours for the background, figures, labels, accent, up, down and the highlighted message.
* Reset everything: back to the original settings.

== Data ==

* Bitcoin price: CoinGecko. Coin data (reward, block time, network hashrate, price): WhatToMine.
* Your server fetches them and shares one copy with every visitor (Bitcoin price at most every minute, coins every 10 minutes). Visitors' browsers then refresh the figures in place, so prices stay current even when your pages are cached.
* If a source is down, the last known figures stay. Coins WhatToMine marks as delayed are flagged in the settings.
* Hashprices move with coin prices and network difficulty; they are estimates, not guarantees.

== Changelog ==

= 1.0.0 =
* First release.

=== Mine Mirth Purchase Orders ===
Requires at least: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later

Create, edit and track purchase orders, and download each one as a PDF in Mine Mirth branding. Products come from your WooCommerce shop (or type your own), with your prices, shipping, fees, taxes and the ship-to address. The PDF shows your Oregon address, email and phone in the website's fonts and colours.

== Where to find it ==

The "Purchase orders" menu in wp-admin: All purchase orders, Add new, Settings. Shop managers and administrators can use it.

== First steps ==

1. Purchase orders › Settings: check the company name (Mine Mirth LLC), your Oregon address, email and phone. They come from the theme's contact settings (Appearance › Customize › Mine Mirth · Footer › Contact details) when you leave them empty here. There is no ship-from address on the PDF, only these details.
2. Click "Preview a sample PDF" to see the design.
3. Purchase orders › Add new.

== Making a purchase order ==

* Ship to: name, company, street, unit, city, state, ZIP, country, phone and email. To reuse an address, type a name, email or company in "Fill from a past order or customer". It searches earlier purchase orders, WooCommerce orders and customer accounts.
* Products: type in the product box to search your shop by name or SKU. Picking one fills in its name, SKU, specs (hashrate, power, efficiency) and current price; you can change any of them for this order. Type anything that is not in the shop (setup, hosting, a used miner) for a custom item. Add as many lines as you need.
* Shipping: a label (for example "Freight (UPS Ground, insured)") and an amount.
* Fees and discounts: as an amount or a percent of the products subtotal. A negative amount is a discount.
* Taxes: as a percent or an amount. Percent taxes use the products subtotal, or the subtotal plus shipping and fees when "Also tax shipping and fees" is ticked.
* Amount paid: what the customer has paid so far. The PDF then shows the amount paid and the balance due. "Paid in full" fills in the total.
* Details: the title on the PDF (Purchase order, Invoice, Proforma invoice or Quote), number, currency, date, payment due date, payment terms and the customer's own reference.
* Shipment tracking: carrier and tracking number, printed on the PDF when filled in.
* Notes for the customer, how to pay and terms are printed on the PDF. The private note is never printed.

Totals update as you type. Save, or "Save & view PDF" to open the PDF in a new tab, or "Download PDF". Ctrl+S (Cmd+S on a Mac) saves.

== Statuses ==

Draft, Sent, Pending payment, Partially paid, Paid, Processing, On hold, Shipped, Delivered, Completed, Cancelled and Refunded. The current status and the date it changed are shown on the PDF (untick "Show the status on the PDF" to hide them). Draft, Paid, Cancelled and Refunded also print a stamp. Every change is kept in the status history with the date, who made it and an optional note.

Rename statuses, change their colours and stamps, or add your own in Settings. Change many purchase orders at once from the list with the bulk action "Change status to …".

Choosing "Paid" fills in the amount paid with the total when nothing was entered yet.

== The list ==

Shows the number, ship-to, products, date, status, total and balance due of every purchase order, with tabs per status, search (number, name, company, email, product, SKU, reference) and sorting. At the top: the balance due on open purchase orders, this month's value and how many are ready to ship. Each row can be edited, viewed or downloaded as a PDF, duplicated (as a new draft with today's date) or moved to the Trash. Purchase orders in the Trash can be restored.

== Numbering ==

Numbers count up from 1001 with the prefix MM-PO- (MM-PO-1001, MM-PO-1002…). Change the prefix, digits and next number in Settings. You can also type a number by hand; you are warned if another purchase order uses it.

== The PDF ==

US Letter or A4, with the dark header from the website (or a light one that saves printer ink), the Mine Mirth mark or your own logo (PNG or JPG), and the brand fonts Sora, Inter and JetBrains Mono embedded, so it looks the same everywhere. Long orders continue on more pages with the table heading repeated and "Page 1 of 2" in the footer. The email address and phone number are clickable.

The PDF is made on your server by the plugin itself: no other plugin, service or library is needed. It needs the PHP extensions mbstring and zlib, which almost every host has.

== Fonts ==

Sora, Inter and JetBrains Mono are licensed under the SIL Open Font License 1.1 (fonts/OFL.txt).

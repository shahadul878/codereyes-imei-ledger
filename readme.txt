=== IMEI Phone Ledger ===
Contributors: shahadul878
Tags: imei, mobile shop, inventory, second hand, ledger
Requires at least: 6.4
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A purchase and sale ledger for second-hand mobile phones, tracked by IMEI number.

== Description ==

IMEI Phone Ledger is built for shops that buy and sell used phones. Every phone is recorded by its IMEI, so you can find its full history in seconds: who sold it to you, for how much, who you sold it to, and what you earned.

**Features**

* Record purchases: IMEI 1 and IMEI 2, brand, model, storage, color, condition, price and the seller's name, mobile number, NID and address.
* Record sales with the buyer's details. A live preview shows the profit or loss as you type the price.
* Search by full or partial IMEI, name, mobile number, NID or model. Bengali digits (০-৯) work too.
* IMEI check: the 15-digit Luhn check digit catches typing mistakes.
* Duplicate protection: a phone that is already in stock cannot be added twice. A phone that was sold before shows a warning with the earlier record, and you must confirm before buying it again.
* Profit per phone, plus a summary of stock, sales and profit.
* Reports for any date range, with quick ranges (today, last 7 days, this month, last month, this year).
* CSV export that opens correctly in Excel: Bengali text is kept, IMEIs stay as text, and spreadsheet formulas are neutralised.
* Cancel a sale entered by mistake; the phone goes back into stock.
* A link to check an IMEI on BTRC NEIR.

**Privacy**

Seller and buyer details, including NID numbers, are stored in your own WordPress database. Only users with the `manage_imei_ledger` capability can see them. This is given to Administrators, and to Shop Managers when WooCommerce is installed.

== Installation ==

1. Upload the `imei-phone-ledger` folder to `/wp-content/plugins/`.
2. Activate the plugin.
3. Open **IMEI Ledger → Add Purchase** to record your first phone.

== Frequently Asked Questions ==

= Where do I find a phone's IMEI? =

Dial `*#06#` on the phone. Dual-SIM phones show two IMEIs.

= Can the same phone be bought twice? =

Yes. If you sell a phone and later buy it back, the plugin warns you and shows the earlier record. After you confirm, it creates a new record, so the full history is kept. A phone that is still in stock cannot be added again.

= Does uninstalling delete my records? =

No. The ledger is kept by default so a mistaken uninstall cannot wipe your shop's history. To delete everything, add `define( 'IMEI_LEDGER_DELETE_DATA', true );` to `wp-config.php` and then uninstall.

= Can I change the currency symbol? =

Yes. The default is ৳. Use the `imei_ledger_currency_symbol` filter:

`add_filter( 'imei_ledger_currency_symbol', fn() => 'Tk ' );`

= Who can use the ledger? =

Any user with the `manage_imei_ledger` capability. Use a role editor plugin to give it to other roles.

== Changelog ==

= 1.0.0 =
* First release.

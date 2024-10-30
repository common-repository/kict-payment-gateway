=== Plugin Name ===
Contributors: kict
Donate link: https://www.k-ict.org/Wordpress/donate.php
Tags: online payment, FPX, KPG, Malaysia, MyClear, Internet Banking, CIMBclicks, Maybank2u, Public Bank, Alliance Online, Affin Bank, HSBC, Bank Islam, Hong Leong Connect, RHB Now, Bank Rakyat, AmBank, Standard Chartered, OCBC Bank, Bank Muamalat, United Overseas Bank, Maybank2e, Bank Simpanan Nasional
Requires at least: 4.3
Tested up to: 4.9.4
Stable tag: 2.12
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Plugin for the KPG registered sellers to let their buyers pay using Malaysia saving and current accounts.

== Description ==

FPX is the Internet Banking Payment Gateway by PayNet (previously known as MyClear, the subsidiary of Bank Negara Malaysia / Central Bank of Malaysia) that permits the buyers to pay using their saving and current bank accounts (Malaysia banks). <a href="https://www.k-ict.org/kpg/" target="KICTwindow">KPG</a> or KPay is the web-based application software developed by <a href="http://www.k-ict.org/" target="KICTwindow">K-ICT</a> to simplify FPX usage.

There are currently seventeen (17) Internet Banking available those are CIMBclicks [CIMB Bank Berhad], Maybank2u [Malayan Banking Berhad / Maybank], Public Bank Berhad, Alliance Online, Affin Bank, HSBC, Bank Islam, Hong Leong Connect, RHB Now, iRakyat [Bank Rakyat], AmBank, Standard Chartered, OCBC Bank, Bank Muamalat, United Overseas Bank, Maybank2e, and Bank Simpanan Nasional (BSN).

== Installation ==

1. Install the WooCommerce plugin from https://wordpress.org/plugins/woocommerce/ . Skip this step if you have already installed it.
2. Upload the plugin files to the `/wp-content/plugins/KICT_Payment_Gateway` directory, or install the plugin through the WordPress plugins screen directly.
3. Activate the plugin through the 'Plugins' screen in WordPress.
4. Click on the Settings link at the KICT_Payment_Gateway installed plugin page, or go to WooCommerce->Settings->Checkout and click on KPG to configure the plugin.
5. Check Enable KICT Payment Gateway for Enable / Disable option to enable this plugin on the WooCommerce checkout page.
6. Request for the KPay Seller Account Registration by providing your information at <a href="https://www.k-ict.org/v4/kict-payment-gateway-kpay/" target="KICTwindow">https://www.k-ict.org/v4/kict-payment-gateway-kpay/</a>.

== Frequently Asked Questions ==

= What is FPX? =

FPX is the Internet Banking Online Payment Gateway name for Malaysia banks. It is provided by PayNet which offer the buyers to pay using their Internet Banking saving and current account.

= What is KPay? =

KPG is KICT Payment Gateway that enable the online sellers to accept online payment using their shopping cart. It is developed by Konsortium ICT Pantai Timur (formerly known as KICT), an Islamic ICT service and solution provider in Malaysia.

= Is this a legal in Malaysia? =

Yes. KICT has been granted the access by PayNet to accept online payment using the awarded FPX Exchange since year 2015.

= What is KPG Login ID, and KPG Password in the plugin configuration page? =

They are the seller information with MD5 hash string / text. Seller must be a registered users at KPG. Visit <a href="https://www.k-ict.org/v4/kict-payment-gateway-kpay/" target="KICTwindow">https://www.k-ict.org/v4/kict-payment-gateway-kpay/</a> for more details, and registration request form.

= What is Portal Key in the plugin configuration page? =

Portal Key is the identification of the seller shopping cart in KPG. Seller must be a registered users at KPG. Visit <a href="https://www.k-ict.org/v4/kict-payment-gateway-kpay/" target="KICTwindow">https://www.k-ict.org/v4/kict-payment-gateway-kpay/</a> for more details, and registration request form.

= What is Order Payment Description in the plugin configuration page? =

Order Payment Description is the field that describes the purpose of a payment. There are currently three (3) types and can be chosen to suit your billing format.

= Is there any configurable options? =

No. There is no more options available for this plugin. Just register as KPG seller, create your shopping cart with WooCommerce, install this plugin, activate, and you are ready to accept Internet Banking online payment from Malaysia banks.

= Will it work with my theme? =

Yes, as long as you do not make any modifications to WooCommerce codes. This plugin is only developed to enable seller to accept Internet Banking online payment from Malaysia banks.

= How can I provide feedback or suggestions? =

Drop your feedback, suggestions, or comments (also if you would like to file a bug) at the WordPress plugin page and we may consider them in the next version. You may also contact us by sending email to support@k-ict.org with the subject "WordPress plugin : KICT Payment Gateway".

= Is there any paid version? =

There is no paid version available for the moment, since this plugin is only usable by the registered sellers of KPG.

== Screenshots ==

1. This plugin in the installed plugins list page (Plugins-Installed Plugins).
2. This plugin in the WooCommerce Settings page (WooCommerce->Settings-Checkout-KPG).
3. The rest options of this plugin in the WooCommerce Settings page (WooCommerce->Settings-Checkout-KPG).
4. This plugin in the WooCommerce Gateway Display Order page (WooCommerce->Settings-Checkout).
5. The Order Payment Description page (WooCommerce pay order page).
6. The landing page of KPG (after buyer clicked on Proceed to FPX button). Buyer MUST agree to the FPX terms and conditions.
7. The FPX terms and conditions page.
8. List of available banks.
9. The KPG transaction receipt page.

== Changelog ==

= 2.12 =
* Open the plugin description URL in the new tab instead of the current tab for better user experience.

= 2.11 =
* Change the plugin description at the dashboard from email address to the URL.

= 2.1 =
* Update the gateway provider name, MyClear to PayNet.
* Compatibility testing for the current WordPress version.
* Remove the EV SSL notice since we are not using that SSL certificate for the moment.

= 2.0 =
* Able to check for the availability of WooCommerce plugin and display error message if no WooCommerce found or inactive.
* Display proper error message for empty KPG user login ID, password, and portal key.
* Require MD5 hashed user login ID and password to connect to the API.
* Adding three (3) options for Order Payment Description those are Simple field, Year and month (YYYY/MM) with simple field, and Date (YYYY/MM/DD) with simple field.
* Require the buyer to fill up Order Payment Description before connecting to KPG.
* Display KPG seller name, and security notice to the buyer at the Order Payment Description page.
* Display error message at the Order Payment Description page, and prevent the buyers from continuing with the payment if there were invalid seller information provided.
* Improve the response callback function from KPG server via API and scheduling (cron).
* Redirect the buyer to the KPG transaction receipt page in order to prevent from triggering security warning for shopping cart with no HTTPS.

= 1.1 =
* Use native JavaScript instead on jQuery functions for the form submission on the payment confirmation page due to some WooCommerce child-themes are unable to load jQuery at that stage.

= 1.0 =
* Initial release of this plugin.

== Upgrade Notice ==

= 1.0 =
Not applicable.


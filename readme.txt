=== Yoopay - WooCommerce Gateway ===
Contributors: itconsultis
Tags: woocommerce, yoopay, payment, gateway 
Requires at least: 4.1
Tested up to: 4.1
Stable tag: 4.3
License: MIT
License URI: https://opensource.org/licenses/MIT

Woocommerce gateway plugin based on the Yoopay (http://yoopay.cn/) payment gateway api version 11.

== Description ==

This plugin provides an interaction with Yoopay's payment api and allows payment charging through Yoopay's gateway.

The plugin allows as well the usage of the provided sandbox mode to test the settings of the plugin and of the Yoopay control panel.

== Installation ==

### Manual installation

1. Download the plugin and extract the folder (or clone / add as a git submodule the repository directly in place)
2. Place the plugin folder inside your plugins folder `/WORDPRESS_ROOT/wp-content/plugins/`
3. Enable the plugin in the plugins section of your wordpress installation `http://www.mywebsite.com/wp-admin/plugins.php`
4. Configure the plugin

### Wordpress manual plugin installation

1. Download the plugin zip file
2. Upload the plugin in the Add plugin page `http://www.mywebsite.com/wp-admin/plugin-install.php?tab=upload`
3. Enable the plugin in the plugins section of your wordpress installation `http://www.mywebsite.com/wp-admin/plugins.php`
4. Configure the plugin

### Wordpress automatic plugin installation

1. Open the plugin installation page on your wordpress installation `http://www.mywebsite.com/wp-admin/plugin-install.php`
2. Search the plugin 
3. Click install now
4. Configure the plugin

### Setup

The backend configuration can be accessed from the WooCommerce checkout settings `http://www.mysite.com/wp-admin/admin.php?page=wc-settings&tab=checkout&section=wc_gateway_yoopay`

![Woocommerce configuration](assets/screenshot-1.png)

#### Sections

- `Enable / Disable` Enables the payment method in the frontend
- `Title` Payment title the customer will see during the checkout process.
- `Description` Payment description the customer will see during the checkout process.
- `Item Name` Item Name the customer will see in the yoopay payment window. Available variables:
    - `{{site_name}}` The website name as specified in the general wordpress settings
    - `{{date}}` The date of the transaction
- `Item Body` Item Body the customer will see in the yoopay payment window. Available variables:
    - `{{items_in_cart}}` A list of the names of the bought items
- `Yoopay Merchant API key` Can be found on the [Yoopay api page](https://yoopay.cn/ypservice/api) 
- `Yoopay login email` The email used to login on the yoopay platform
- `Enabled payment methods` Enabled payment methods that will be displayed on the payment page.
- `Invoice on Yoopay` Indicate whether to show the invoice collection form on the payment page.
- `Auto submit form` If checked the form in the receipt page will be authomatically submitted.
- `Yoopay Sandbox Mode` If checked, places the payment gateway in sandbox mode.
- `Sandbox target status` Selects the sandbox target status. (only used when the module is in sandbox mode)
- `Yoopay Debug Mode` Place the payment gateway in debug mode. Will log to woocommerce/logs/

== Frequently Asked Questions ==

= Do I need a Yoopay merchant account before I can use the Yoopay - WooCommerce Gateway plugin? =

Yes. In order to use this plugin you will need a merchant services account. You can have more information here: https://yoopay.cn/account/signup_select

= What is the cost for the gateway plugin? =

This plugin is a FREE download, for the costs of the gateway usage please contact directly with Yoopay.

== Screenshots ==

* Example of backend configuration for the plugin.

== Changelog ==

= 2.0 =
* Added refund support.

= 1.1 =
* Changed the "sandbox" field to required in the api.

= 1.0 =
* First Release.
* Integration with yoopay payment api version 11.

== Upgrade Notice ==

= 2.0 =
* Added refund support.

= 1.1 =
* Changed the "sandbox" field to required in the api.

= 1.0 =
* First release of the plugin, compatible with yoopay payment api version 11.

=== Channel3 for WooCommerce ===
Contributors: channel3
Tags: woocommerce, integration, catalog, products, sync
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Sync your WooCommerce product catalog to Channel3.

== Description ==

Channel3 for WooCommerce lets merchants connect their WooCommerce store to Channel3 to enable product catalog synchronization across sales channels.

= External service =

This plugin connects to the Channel3 service (`trychannel3.com` / `api.trychannel3.com`) to complete authorization and to keep connection status in sync.

When connected, Channel3 has read-only access to product catalog data (including product names, descriptions, prices, inventory, images, and categories). No customer personal data is shared by this plugin as part of the integration.

Privacy policy: https://trychannel3.com/privacy

== Installation ==

1. Install and activate the plugin.
2. Go to **WooCommerce → Settings → Integrations → Channel3**.
3. In your Channel3 dashboard, choose **Connect WooCommerce Store** and follow the prompts.

== Frequently Asked Questions ==

= Does Channel3 get access to customer or order data? =

No. The integration is designed for read-only access to product catalog data.

== Development ==

The source code for this plugin is available on GitHub:
https://github.com/channel3-ai/channel3-for-woocommerce

= Building from Source =

1. Clone the repository
2. Run `npm install` to install dependencies
3. Run `npm run build` to compile assets

The `src/` directory contains the uncompiled JavaScript and SCSS source files.

== Changelog ==

= 1.0.0 =
* Initial release.


# Channel3 for WooCommerce

Sync your WooCommerce product catalog to Channel3.

## Description

Channel3 for WooCommerce allows merchants to connect their WooCommerce store to Channel3, enabling product catalog synchronization across multiple sales channels.

### Features

- **OAuth Connection** - Secure connection flow initiated from Channel3
- **Read-Only Access** - Channel3 only has read access to your product catalog
- **Easy Setup** - Simple authorization process takes just 2 minutes
- **Privacy Focused** - No customer personal data is shared

## Requirements

- WordPress 6.0 or higher
- WooCommerce 8.0 or higher
- PHP 7.4 or higher

## Installation

1. Upload the `channel3-for-woocommerce` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to your Channel3 dashboard and click "Connect WooCommerce Store"
4. Authorize the connection when prompted

## Configuration

After installation, you can manage your Channel3 connection at:
**WooCommerce > Settings > Integrations > Channel3**

## Development

### Prerequisites

- [Node.js](https://nodejs.org/) with NPM
- [Composer](https://getcomposer.org/download/)
- [Docker](https://www.docker.com/) (for wp-env)

### Setup

```bash
# Install dependencies
npm install

# Build assets
npm run build

# Start development environment (optional)
npm run wp-env start
```

### Development Commands

- `npm run start` - Start development build with watch mode
- `npm run build` - Build production assets
- `npm run plugin-zip` - Create distributable ZIP for WordPress.org
- `npm run lint:js` - Lint JavaScript files
- `npm run lint:css` - Lint CSS/SCSS files

### Building for Distribution

To create a plugin ZIP for WordPress.org submission or distribution:

```bash
# Build production assets and create ZIP
npm run build && npm run plugin-zip
```

This creates `channel3-for-woocommerce.zip` in the project root, ready for:
- WordPress.org plugin submission
- Manual installation testing
- Distribution to beta testers

The ZIP includes only production-ready files (no `node_modules/`, `vendor/`, or dev tools).

### Local Development

The plugin automatically detects when you're running on `localhost` or `127.0.0.1` and points to `https://channel3.ngrok.dev` for the Channel3 backend.

If you need to use a different backend URL, add this to your `wp-config.php`:

```php
define( 'CHANNEL3_BASE_URL', 'http://localhost:8000' );
```

## Changelog

See [changelog.txt](changelog.txt) for a list of changes.

## Privacy

When connected, Channel3 has read-only access to:
- Product names and descriptions
- Product prices and inventory
- Product images and categories

Channel3 does **not** have access to:
- Customer personal data
- Order information
- Payment details

For more information, see the [Channel3 Privacy Policy](https://trychannel3.com/privacy).

## Support

For support, please visit [trychannel3.com](https://trychannel3.com) or contact our support team.

## License

This plugin is licensed under the GNU General Public License v3.0.

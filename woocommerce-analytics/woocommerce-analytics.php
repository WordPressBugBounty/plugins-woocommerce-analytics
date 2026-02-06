<?php
/**
 * Plugin Name:          WooCommerce Analytics
 * Plugin URI:           https://woocommerce.com
 * Description:          Unlock actionable insights to boost sales and maximize your marketing ROI with WooCommerce Analytics.
 * Version:              0.9.12
 * Author:               WooCommerce
 * Author URI:           https://woocommerce.com/
 * Text Domain:          woocommerce-analytics
 *
 * Requires Plugins:     woocommerce
 * Requires PHP:         7.4
 * Tested up to: 6.8
 * Requires at least:    6.5
 * WC tested up to: 10.3
 * WC requires at least: 9.5
 *
 * License:              GNU General Public License v3.0
 * License URI:          https://www.gnu.org/licenses/gpl-3.0.html
 */

use Automattic\WooCommerce\Analytics\Autoloader;
use Automattic\WooCommerce\Analytics\Internal\Plugin;

defined( 'ABSPATH' ) || exit;

define( 'WC_ANALYTICS_VERSION', '0.9.12' ); // WRCS: DEFINED_VERSION.
define( 'WC_ANALYTICS_MIN_PHP_VER', '7.4' );
define( 'WC_ANALYTICS_MIN_WC_VER', '9.5.0' );
define( 'WC_ANALYTICS_FILE', __FILE__ );
define( 'WC_ANALYTICS_ABSPATH', plugin_dir_path( __FILE__ ) );

// Load and initialize the autoloader.
require_once __DIR__ . '/src/Autoloader.php';
if ( ! Autoloader::init() ) {
	return;
}
/**
 * Global function to get the plugin instance.
 *
 * @return Plugin
 */
function woocommerce_analytics(): Plugin {
	return Plugin::get_instance();
}

// Initialize the plugin.
woocommerce_analytics();

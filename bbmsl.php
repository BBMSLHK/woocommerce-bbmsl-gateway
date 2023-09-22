<?php
/**
 * Plugin Name:  BBMSL Payment Gateway
 * Description:  Online payment solution for Hong Kong merchants. Supports Visa, Master, AMEX, Alipay, Wechat Pay, Apple Pay, Google Pay.
 * Author:       Coding Free Limited for BBMSL
 * Author URI:   https://www.bbmsl.com/
 * Version:      1.0.20
 * Requires PHP: 7.4
 * Text Domain:  bbmsl-gateway
 * Domain Path:  /i18n/languages/
 * License:      GNU General Public License v3.0
 * License URI:  http://www.gnu.org/licenses/gpl-3.0.html
 *
 * @package   bbmsl-gateway
 * @author    Coding Free Limited for BBMSL
 * @category  Admin
 * @copyright Copyright (c) 2022 BBMSL Limited
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 *
 */

namespace BBMSL;

defined( 'ABSPATH' ) || exit;

final class BBMSL
{
	public static $version = 'prod.1.0.20';
	public function __construct()
	{
		// define global constants
		if( ! defined( 'BBMSL_PLUGIN_FILE' ) ) {
			define( 'BBMSL_PLUGIN_FILE', __FILE__ );
		}
		if( ! defined( 'BBMSL_PLUGIN_DIR' ) ) {
			define( 'BBMSL_PLUGIN_DIR', __DIR__ . DIRECTORY_SEPARATOR );
		}
		if( ! defined( 'BBMSL_PLUGIN_BASE_URL' ) ) {
			define( 'BBMSL_PLUGIN_BASE_URL', plugin_dir_url( BBMSL_PLUGIN_FILE ) );
		}

		add_action( 'plugins_loaded', function()
	{
			$plugin_files = array(
				'vendor' . DIRECTORY_SEPARATOR . 'autoload.php',
				'bootstrap' . DIRECTORY_SEPARATOR . 'Constants.php',
				'bootstrap' . DIRECTORY_SEPARATOR . 'Plugin.php',
				'controllers' . DIRECTORY_SEPARATOR . 'RefundController.php',
				'controllers' . DIRECTORY_SEPARATOR . 'WebhookController.php',
				'plugin' . DIRECTORY_SEPARATOR . 'Notice.php',
				'plugin' . DIRECTORY_SEPARATOR . 'Option.php',
				'plugin' . DIRECTORY_SEPARATOR . 'Setup.php',
				'plugin' . DIRECTORY_SEPARATOR . 'WooCommerce.php',
				'plugin' . DIRECTORY_SEPARATOR . 'WordPress.php',
				'sdk' . DIRECTORY_SEPARATOR . 'BBMSL_SDK.php',
				'sdk' . DIRECTORY_SEPARATOR . 'BBMSL.php',
				'sdk' . DIRECTORY_SEPARATOR . 'Log.php',
				'sdk' . DIRECTORY_SEPARATOR . 'SSL.php',
				'sdk' . DIRECTORY_SEPARATOR . 'Utility.php',
				'sdk' . DIRECTORY_SEPARATOR . 'Webhook.php',
			);
			foreach( $plugin_files as $file ){
				include_once( BBMSL_PLUGIN_DIR . $file );
			}
			Bootstrap\Plugin::bootstrap();
			return true;
		} );

	}
}

new BBMSL();
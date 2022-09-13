<?php

/**
 * class-bbmsl.php
 *
 * WordPress main library file
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\BBMSL
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.8
 * @since      File available since initial Release.
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

use BBMSL\Sdk\Setup;
use BBMSL\Sdk\Notice;
use BBMSL\Sdk\Utility;
use BBMSL\Sdk\Webhook;
use BBMSL\Sdk\BBMSL_SDK;
use BBMSL\Sdk\WordPress;
use \WP_Screen;
use \WC_Order_Item_Product;

defined( 'ABSPATH' ) or exit;
final class BBMSL{
	public static $version = 'prod.1.0.8';
	protected static $_instance = null;

	public static function instance() {
		if( !isset( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	public const TEXT_DOMAIN	= 'bbmsl-gateway';

	public const POSTED_KEY		= 'BBMSL';
	public const OPTIONS_PREFIX	= 'bbmsl_';

	public const TESTING_PORTAL_LINK		= 'https://merchant.sit.bbmsl.com/user/login';
	public const PRODUCTION_PORTAL_LINK		= 'https://merchant.bbmsl.com/user/login';
	
	public const CURRENCY_HKD	= 'HKD';
	public const CURRENCY_USD	= 'USD';

	public const LANGUAGE_EN	= 'en';
	public const LANGUAGE_TC	= 'zh-HK';

	public const PARAM_GATEWAY_DISPLAY_NAME			= 'display_name';
	public const PARAM_GATEWAY_DESCRIPTION			= 'description';
	public const PARAM_GATEWAY_THANK_YOU_PAGE		= 'thank_you_page';
	public const PARAM_GATEWAY_EMAIL_CONTENT		= 'email_footer_content';

	public const PARAM_GATEWAY_DISPLAY_NAME_TC		= 'display_name_tc';
	public const PARAM_GATEWAY_DESCRIPTION_TC		= 'description_tc';
	public const PARAM_GATEWAY_THANK_YOU_PAGE_TC	= 'thank_you_page_tc';
	public const PARAM_GATEWAY_EMAIL_CONTENT_TC		= 'email_footer_content_tc';

	public const PARAM_GATEWAY_REFUND				= 'gateway_refund';
	public const PARAM_EXPRESS_CHECKOUT				= 'express_checkout_enabled';
	public const PARAM_SHOW_LANGUAGE_TOOLS			= 'show_language_tools_enabled';
	public const PARAM_SHOW_GATEWAY_BRAND			= 'show_gateway_brand_enabled';
	
	public const PARAM_GATEWAY_MODE 				= 'gateway_mode';
	public const PARAM_GATEWAY_METHODS				= 'gateway_methods';
	public const PARAM_BROWSER_DEFAULT_LANG			= 'en';
	public const PARAM_PRODUCTION_MERCHANT_ID		= 'master_production_merchant_id';
	public const PARAM_PRODUCTION_PUBLIC_KEY		= 'master_production_public_key';
	public const PARAM_PRODUCTION_PRIVATE_KEY		= 'master_production_private_key';
	public const PARAM_PRODUCTION_KEY_LAST_UPDATE	= 'master_production_key_last_update';
	public const PARAM_TESTING_MERCHANT_ID			= 'master_sandbox_merchant_id';
	public const PARAM_TESTING_PUBLIC_KEY			= 'master_sandbox_public_key';
	public const PARAM_TESTING_PRIVATE_KEY			= 'master_sandbox_private_key';
	public const PARAM_TESTING_KEY_LAST_UPDATE		= 'master_sandbox_key_last_update';

	public const META_ORDERING_MODE					= 'bbmsl_ordering_mode';
	public const META_MERCHANT_ID					= 'bbmsl_merchant_id';
	public const META_MERCHANT_REF					= 'bbmsl_merchant_reference';
	public const META_ORDER_ID						= 'bbmsl_order_id';
	public const META_CHECKOUT_LINK					= 'bbmsl_checkout_link';
	public const META_CREATE_ORDER					= 'bbmsl_create_order';
	public const META_LAST_QUERY					= 'bbmsl_last_query';
	public const META_LAST_VOID						= 'bbmsl_last_void';
	public const META_LAST_WEBHOOK					= 'bbmsl_last_webhook';

	public const ACTION_REGEN_PRODUCTION_KEYS		= 'regenerate-production-keys';
	public const ACTION_REGEN_TESTING_KEYS			= 'regenerate-sandbox-keys';

	public const PLUGIN_PRIORITY = 2147483647;

	private static $one_way_prerequisite = false;
	private static $order_cache = array();

	public static function getDefaults() {
		return array(
			static::PARAM_GATEWAY_MODE					=> BBMSL_SDK::MODE_TESTING,
			static::PARAM_GATEWAY_REFUND				=> 0,
			static::PARAM_GATEWAY_METHODS				=> '["alipay_hk","mastercard","visa","wechat_pay_hk"]',
			static::PARAM_GATEWAY_DISPLAY_NAME			=> 'BBMSL',
			static::PARAM_GATEWAY_DESCRIPTION			=> '<p>Pay with BBMSL gateway, your full range payments service provider.</p>',
			static::PARAM_GATEWAY_THANK_YOU_PAGE		=> '<p>Thank you for checking out with BBMSL.</p>',
			static::PARAM_GATEWAY_EMAIL_CONTENT			=> '<p>Payment service powered by BBMSL.</p>',
			// static::PARAM_GATEWAY_DISPLAY_NAME_TC		=> 'BBMSL',
			// static::PARAM_GATEWAY_DESCRIPTION_TC		=> '<p>Pay with BBMSL gateway, your full range payments service provider.</p>',
			// static::PARAM_GATEWAY_THANK_YOU_PAGE_TC		=> '<p>Thank you for checking out with BBMSL.</p>',
			// static::PARAM_GATEWAY_EMAIL_CONTENT_TC		=> '<p>Payment service powered by BBMSL.</p>',
			static::PARAM_EXPRESS_CHECKOUT				=> 0,
			static::PARAM_SHOW_LANGUAGE_TOOLS			=> 1,
			static::PARAM_SHOW_GATEWAY_BRAND			=> 1,
			static::PARAM_BROWSER_DEFAULT_LANG			=> static::LANGUAGE_EN,
		);
	}

	public static function coeaseDefaults() {
		$defaults = static::getDefaults();
		foreach( $defaults as $key => $value ) {
			if( !WordPress::has_option( $key ) ) {
				if( isset( $value ) ) {
					WordPress::update_option( $key, $value );
				}
			}
		}
		return true;
	}

	public static function getMethods() {
		return array(
			'alipay_cn' => array(
				'name'	=> 'Alipay',
				'logo'	=> 'public/images/methods/Alipay_CN.png',
				'code'	=> 'ALIPAYCN',
			),
			'alipay_hk' => array(
				'name'	=> 'AlipayHK',
				'logo'	=> 'public/images/methods/Alipay_HK.png',
				'code'	=> 'ALIPA_HK',
			),
			'apple_pay' => array(
				'name'	=> 'Apple Pay',
				'logo'	=> 'public/images/methods/ApplePay.png',
				'code'	=> 'APPLEPAY',
			),
			'google_pay' => array(
				'name'	=> 'Google Pay',
				'logo'	=> 'public/images/methods/Googlepay.png',
				'code'	=> 'GOOGLEPAY',
			),
			'mastercard' => array(
				'name'	=> 'Mastercard',
				'logo'	=> 'public/images/methods/Mastercard.png',
				'code'	=> 'CARD',
			),
			'visa' => array(
				'name'	=> 'Visa',
				'logo'	=> 'public/images/methods/VISA.png',
				'code'	=> 'CARD',
			),
			'wechat_pay_hk' => array(
				'name'	=> 'WeChat Pay HK',
				'logo'	=> 'public/images/methods/Wechatpay.png',
				'code'	=> 'WECHATPAY',
			),
		);
	}

	public static function init() {
		if( WordPress::isPluginInstalled( 'woocommerce/woocommerce.php' ) ) {
			include_once( BBMSL_PLUGIN_DIR . '/sdk/class-payment-gateway.php' );
			Webhook::handleWebhook();
			if( function_exists( 'is_admin' ) ? is_admin() : true) {

				$plugin_file = basename( BBMSL_PLUGIN_DIR ) . '/' . basename( BBMSL_PLUGIN_FILE );
				add_action( 'admin_enqueue_scripts', function() {
					if( WordPress::isScreens( array( 'woocommerce_page_wc-settings', 'shop_order' ) ) ) {
						$asset_base_url = plugin_dir_url( BBMSL_PLUGIN_FILE );
						wp_enqueue_style( 'bbmsl-fonts',				$asset_base_url . 'public/css/fonts.style.css', array (), BBMSL::$version, 'all' );
						wp_enqueue_style( 'bbmsl-style',				$asset_base_url . 'public/css/admin-style.css', array (), BBMSL::$version, 'all' );
						wp_enqueue_style( 'bbmsl-bootstrap',			$asset_base_url . 'public/css/bootstrap-grid.min.css', array (), BBMSL::$version, 'all' );
						wp_enqueue_style( 'jquery-ui' );
						wp_enqueue_script( 'jquery' );
						wp_enqueue_script( 'jquery-ui-sortable' );
						wp_enqueue_script( 'bbmsl-settings', 			$asset_base_url . 'public/js/woocommerce_payment_settings/scripts.js', array(), BBMSL::$version, true );
						wp_enqueue_script( 'bbmsl-tinymce',				$asset_base_url . 'public/plugins/tinymce/tinymce.min.js', array(), BBMSL::$version, true );
						wp_enqueue_script( 'bbmsl-tinymce-config', 		$asset_base_url . 'public/js/woocommerce_payment_settings/tinymce.js', array( 'bbmsl-tinymce' ), BBMSL::$version, true );
						wp_enqueue_script( 'bbmsl-sortable-methods',	$asset_base_url . 'public/js/woocommerce_payment_settings/sortable_methods.js', array( 'jquery', 'jquery-ui-sortable' ), BBMSL::$version, true );
					}
				}, BBMSL::PLUGIN_PRIORITY );
				add_filter( 'plugin_action_links_'. $plugin_file,		array( Setup::class, 'setupPluginActionLinks' ) );
				add_filter( 'plugin_row_meta',							array( Setup::class, 'setupPluginMeta' ), 10, 2 );
				add_filter( 'woocommerce_gateway_title',				array( Setup::class, 'setupPluginGatewayTitle' ), BBMSL::PLUGIN_PRIORITY );
				add_filter( 'woocommerce_gateway_description',			array( Setup::class, 'setupPluginGatewayDescription' ), BBMSL::PLUGIN_PRIORITY );
				add_filter( 'woocommerce_bbmsl_icon',					array( Setup::class, 'setupPluginGatewayIcon' ), BBMSL::PLUGIN_PRIORITY );
				add_action( 'admin_head',			 					array( Setup::class, 'setupOrderDetails' ), BBMSL::PLUGIN_PRIORITY );
				add_action( 'post_updated',								array( Setup::class, 'setupVoidHandling' ), 0 );
				add_action( 'admin_notices',							array( Notice::class, 'displaySetupRequiredNotice' ), 0 );
				add_action( 'admin_notices',							array( Notice::class, 'recall' ) );
				add_action( 'wp_ajax_woocommerce_refund_line_items',	array( Setup::class, 'setupRefundHandling' ), -1 * BBMSL::PLUGIN_PRIORITY );
				add_action( 'woocommerce_email_footer',					array( Setup::class, 'setupOrderEmailFooter' ), -1 * BBMSL::PLUGIN_PRIORITY );
			}
			add_action( 'wp_enqueue_scripts', function() {
				wp_enqueue_style( 'bbmsl-css', plugin_dir_url( BBMSL_PLUGIN_FILE) . 'public/css/public-style.css' );
			}, BBMSL::PLUGIN_PRIORITY );
			add_filter( 'woocommerce_payment_gateways',					array( Setup::class, 'setupPluginAddGateway' ), BBMSL::PLUGIN_PRIORITY );
			add_action( 'woocommerce_widget_shopping_cart_buttons',		array( Setup::class, 'setupShoppingCart' ), 0 );
			add_action( 'wp_footer',									array( Setup::class, 'setupExpressCheckoutAesthetics' ), BBMSL::PLUGIN_PRIORITY );
		}else{
			add_action( 'admin_notices',								array( Notice::class, 'displayWooCommerceRequiredNotice' ) );
		}
		static::coeaseDefaults();
	}

	// PLUGIN FUNCTIONS
	public static function ensureGatewayMode() {
		if( WordPress::has_option( static::PARAM_GATEWAY_MODE ) ) {
			$mode = WordPress::get_option( static::PARAM_GATEWAY_MODE, BBMSL_SDK::MODE_TESTING );
			if( BBMSL_SDK::isModeAccepted( $mode ) ) {
				return $mode;
			} else {
				Notice::flash( esc_attr__( 'Gateway mode %s is not valid, reverting to testing mode.', 'bbmsl-gateway' ), Notice::TYPE_WARNING );
			}
			WordPress::update_option( static::PARAM_GATEWAY_MODE, BBMSL_SDK::MODE_TESTING );
			return BBMSL_SDK::MODE_TESTING;
		}
	}

	public static function ready() {
		$mode = static::ensureGatewayMode();
		if( $mode == BBMSL_SDK::MODE_PRODUCTION ) {
			return	WordPress::has_option( static::PARAM_PRODUCTION_MERCHANT_ID ) &&
					BBMSL_SDK::check_key( WordPress::get_option( static::PARAM_PRODUCTION_PUBLIC_KEY ) ) &&
					BBMSL_SDK::check_key( WordPress::get_option( static::PARAM_PRODUCTION_PRIVATE_KEY ) );
		}else if( $mode == BBMSL_SDK::MODE_TESTING ) {
			return	WordPress::has_option( static::PARAM_TESTING_MERCHANT_ID ) &&
					BBMSL_SDK::check_key( WordPress::get_option( static::PARAM_TESTING_PUBLIC_KEY ) ) &&
					BBMSL_SDK::check_key( WordPress::get_option( static::PARAM_TESTING_PRIVATE_KEY ) );
		}
		return false;
	}

	public static function getMerchantID() {
		$mode = static::ensureGatewayMode();
		if( $mode == BBMSL_SDK::MODE_PRODUCTION ) {
			return	WordPress::get_option( static::PARAM_PRODUCTION_MERCHANT_ID );
		}else if( $mode == BBMSL_SDK::MODE_TESTING ) {
			return	WordPress::get_option( static::PARAM_TESTING_MERCHANT_ID );
		}
		return false;
	}

	public static function getOfficialKeyFile() {
		$mode = static::ensureGatewayMode();
		if( $mode == BBMSL_SDK::MODE_PRODUCTION ) {
			return BBMSL_PLUGIN_DIR . 'certs/bbmsl_prod_public_key.pem';
		}
		return BBMSL_PLUGIN_DIR . 'certs/bbmsl_test_public_key.pem';
	}

	public static function getRawPostedPayloads() {
		if( 'POST' === $_SERVER[ 'REQUEST_METHOD' ] ) {
			if( isset( $_POST ) && is_array( $_POST ) && sizeof( $_POST ) > 0 ) {
				if( isset( $_POST[ '_bbmsl_nonce' ] ) && is_string( $_POST[ '_bbmsl_nonce' ] ) ) {
					$nounce = trim( $_POST[ '_bbmsl_nonce' ] );
					if( strlen( $nounce ) > 0 && function_exists( 'wp_verify_nonce' ) ) {
						$verify = wp_verify_nonce( $nounce, 'bbmsl-plugin' );
						if( $verify) {
							if( isset( $_POST[ static::POSTED_KEY ] ) && is_array( $_POST[ static::POSTED_KEY ] ) && sizeof( $_POST[ static::POSTED_KEY ] ) > 0 ) {
								return $_POST[ static::POSTED_KEY ];
							}
						}else{
							add_action( 'admin_notices', array( Notice::class, 'displayNonceExpirationNotice' ) );
							return false;
						}
					}
				}
			}
		}
		return false;
	}
	
	// GATEWAY PAYMENT METHDOS
	public static function getCoeasedMethods() {
		$available_methods = static::getMethods();
		$preference = static::getMethodPreference();
		return array_replace( array_flip( $preference ), $available_methods );
	}
	
	public static function getCompiledMethods() {
		$methods = static::getCoeasedMethods();
		$preference = static::getMethodPreference();
		$methods = array_intersect_key( array_flip( $preference ), $methods );
		if( isset( $methods ) && is_array( $methods ) && sizeof( $methods ) > 0 ) {
			$methods = array_column( $methods, 'code' );
			if( isset( $methods ) && is_array( $methods ) && sizeof( $methods ) > 0 ) {
				return implode(',', array_values( array_unique( array_filter( array_map( 'trim', $methods ) ) ) ) );
			}
		}
		return false;
	}

	public static function getMethodLogoHTML() {
		$image_html = array();
		foreach( static::getCoeasedMethods() as $method) {
			$image_html[] = sprintf( '<img class="logo" src="%s" />', plugin_dir_url( BBMSL_PLUGIN_FILE ) . $method[ 'logo' ] );
		}
		return implode( $image_html );
	}

	public static function getLogoURL( bool $color = false ) {
		if( $color) {
			return plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/logo-full-color.min.svg';
		}
		return plugin_dir_url( BBMSL_PLUGIN_FILE ) . 'public/images/logo.min.svg';
	}

	// GATEWAY FUNCTIONS( Reserved for expansion)
	public static function getAcceptedCurrencies() {
		return array(
			static::CURRENCY_HKD,
		);
	}

	public static function isAcceptedCurrency( string $currency = '' ) {
		if( isset( $currency ) && is_string( $currency ) ) {
			$currency = strtoupper( trim( $currency ) );
			return( strlen( $currency ) > 0 && in_array( $currency, static::getAcceptedCurrencies(), true) );
		}
		return false;
	}

	public static function hasSelectedMethod( string $method = '' ) {
		if( isset( $method ) && is_string( $method ) ) {
			$method = trim( $method );
			if( ( ! empty( $method ) ) && in_array( $method, array_keys( static::getMethods() ), true) ) {
				$preference = static::getMethodPreference();
				if( isset( $preference ) && is_array( $preference ) && sizeof( $preference ) > 0 ) {
					return in_array( $method, $preference, true );
				}
			};
		}
		return false;
	}

	public static function getMethodPreference() {
		$option = WordPress::get_option( static::PARAM_GATEWAY_METHODS );
		if( ( !empty( $option ) ) && Utility::isJson( $option ) ) {
			$option = json_decode( $option, true );

			// patch for wechat_pay to wechat_pay_hk
			if( in_array( 'wechat_pay', $option ) ) {
				if( in_array( 'wechat_pay_hk', $option ) ) {
					$option = array_diff( $option, array( 'wechat_pay' ) );
				} else {
					$index = array_search( 'wechat_pay', $option, true );
					if( $index !== false ) {
						$option[ $index ] = 'wechat_pay_hk';
					}
				}
			}

			return $option;
		}
		return [];
	}

	// ORDER FUNCTIONS
	public static function getOrder( string $order_id = '' ) {
		if( isset( $order_id ) && is_string( $order_id ) ) {
			$order_id = trim( $order_id );
			if( strlen( $order_id ) > 0 && function_exists( 'wc_get_order' ) ) {
				return wc_get_order( $order_id );
			}
		}
		return false;
	}

	public static function getOrderID( string $order_reference = '' ) {
		if( isset( $order_reference ) && is_string( $order_reference ) ) {
			$order_reference = trim( $order_reference );
			if( strlen( $order_reference ) > 0 && function_exists( 'wc_get_order_id_by_order_key' ) ) {
				return wc_get_order_id_by_order_key( $order_reference );
			}
		}
		return false;
	}

	public static function getOrderLineItems( $order = null ) {
		if( isset( $order ) ) {
			$line_items = array();
			if( method_exists( $order, 'get_items' ) ) {
				$cart_items = $order->get_items();
				if( isset( $cart_items ) && is_array( $cart_items ) && sizeof( $cart_items ) > 0 ) {
					foreach( $cart_items as $k => $item) {
						if( isset( $item ) && $item instanceof WC_Order_Item_Product) {
							$expected_methods = array( 'get_quantity', 'get_subtotal', 'get_name' );
							$process = true;
							foreach( $expected_methods as $method ) {
								$process = $process && method_exists( $item, $method );
							}
							if( $process) {
								$item_quantity	= intval( $item->get_quantity() );
								$name			= trim( $item->get_name() );
								$unit_price		= doubleval( $item->get_subtotal() / $item_quantity );
								if( $item_quantity > 0 ) {
									$line_items[] = array(
										'quantity' => $item_quantity,
										'priceData' => array(
											'unitAmount' => $unit_price,
											'name' => $name,
										),
									);
								}
							}
						}
					}
				}
			}
			return $line_items;
		}
		return false;
	}

	public static function checkOrderID( int $order_id = 0 ) {
		if( isset( $order_id ) && is_numeric( $order_id ) ) {
			$order_id = intval( $order_id );
			if( $order_id > 0 ) {
				return $order_id;
			}
		}
		return false;
	}

	public static function getOrderMetaByID( int $order_id = 0 ) {
		if( $order_id = static::checkOrderID( $order_id ) ) {
			$meta = get_post_meta( $order_id, BBMSL::META_CREATE_ORDER );
			if( isset( $meta ) && is_array( $meta ) && sizeof( $meta ) > 0 ) {
				$meta = trim( array_values( $meta )[0] );
				if( strlen( $meta ) > 0 ) {
					$metadata = json_decode( $meta, true );
					if( isset( $metadata ) && is_array( $metadata ) ) {
						return $metadata;
					}
				}
			}
		}
		return false;
	}

	public static function getCheckoutURLByID( int $order_id = 0 ) {
		if( $metadata = static::getOrderMetaByID( $order_id ) ) {
			if( isset( $metadata ) && is_array( $metadata ) && sizeof( $metadata ) > 0 ) {
				if( isset( $metadata[ 'checkoutUrl' ] ) && is_string( $metadata[ 'checkoutUrl' ] ) ) {
					$checkout_url = trim( $metadata[ 'checkoutUrl' ] );
					if( isset( $checkout_url ) && strlen( $checkout_url ) > 0 ) {
						return $checkout_url;
					}
				}
			}
		}
		return false;
	}

	public static function getOrderingModeByID( int $order_id = 0 ) {
		if( $order_id = static::checkOrderID( $order_id ) ) {
			$meta = get_post_meta( $order_id, BBMSL::META_ORDERING_MODE );
			if( isset( $meta ) && is_array( $meta ) && sizeof( $meta ) > 0 ) {
				$ordering_mode = trim( array_values( $meta )[0] );
				if( strlen( $ordering_mode ) > 0 && BBMSL_SDK::isModeAccepted( $ordering_mode ) ) {
					return $ordering_mode;
				}
			}
		}
		return BBMSL_SDK::MODE_TESTING;
	}

	public static function getOrdeIdByID( int $order_id = 0 ) {
		if( $order_id = static::checkOrderID( $order_id ) ) {
			$meta = get_post_meta( $order_id, BBMSL::META_ORDER_ID );
			if( isset( $meta ) && is_array( $meta ) && sizeof( $meta ) > 0 ) {
				$order_id = trim( array_values( $meta )[0] );
				if( strlen( $order_id ) > 0 ) {
					return $order_id;
				}
			}
		}
		return static::getGatewayOrderID( $meta );
	}

	public static function getMerchantReferenceByID( int $order_id = 0 ) {
		if( $order_id = static::checkOrderID( $order_id ) ) {
			$meta = get_post_meta( $order_id, BBMSL::META_MERCHANT_REF );
			if( isset( $meta ) && is_array( $meta ) && sizeof( $meta ) > 0 ) {
				$merchant_reference = trim( array_values( $meta )[0] );
				if( strlen( $merchant_reference ) > 0 ) {
					return $merchant_reference;
				}
			}
		}
		return static::getMerchantReference( $meta );
	}

	public static function matchOrderingMode( int $order_id = 0 ) {
		if( $order_id = static::checkOrderID( $order_id ) ) {
			return static::getOrderingModeByID( $order_id ) == static::ensureGatewayMode();
		}
		return true;
	}

	public static function getFallbackUrl() {
		$fallback_functions = array( 'wc_get_checkout_url', 'wc_get_cart_url', 'get_home_url' );
		foreach( $fallback_functions as $function) {
			if( function_exists( $function ) ) {
				$fallback_url = trim( $function() );
				if( strlen( $fallback_url ) > 0 ) {
					return $fallback_url;
				}
			}
		}
		return false;
	}

	public static function getMerchantReference( array $metadata = array() ) {
		if( isset( $metadata[ 'order' ] ) && is_array( $metadata[ 'order' ] ) && sizeof( $metadata[ 'order' ] ) > 0 ) {
			$metaorder = $metadata[ 'order' ];
			if( isset( $metaorder[ 'merchantReference' ] ) && is_string( $metaorder[ 'merchantReference' ] ) ) {
				return trim( $metaorder[ 'merchantReference' ] );
			}
		}
		return null;
	}
	
	public static function getGatewayOrderID( array $metadata = array() ) {
		if( isset( $metadata[ 'order' ] ) && is_array( $metadata[ 'order' ] ) && sizeof( $metadata[ 'order' ] ) > 0 ) {
			$metaorder = $metadata[ 'order' ];
			if( isset( $metaorder[ 'id' ] ) && is_numeric( $metaorder[ 'id' ] ) ) {
				return trim( $metaorder[ 'id' ] . '' );
			}
		}
		return null;
	}

	public static function getOrderPlainMetadata( int $order_id = 0 ) {
		if( $order_id = static::checkOrderID( $order_id ) ) {
			$order = BBMSL::getOrder( $order_id.'' );
			if( method_exists( $order, 'get_data' ) ) {
				$data = $order->get_data();
				if( isset( $data[ 'meta_data' ] ) && is_array( $data[ 'meta_data' ] ) && sizeof( $data[ 'meta_data' ] ) > 0 ) {
					$metadata_collection = $data[ 'meta_data' ];
					if( isset( $metadata_collection ) && is_array( $metadata_collection ) && sizeof( $metadata_collection ) > 0 ) {
						$metadata = array_map( function( $e )use(&$stack) {
							if( method_exists( $e, 'get_data' ) ) {
								$data = $e->get_data();
								if( isset( $data[ 'value' ] ) && is_string( $data[ 'value' ] ) && Utility::isJson( $data[ 'value' ] ) ) {
									$value = json_decode( $data[ 'value' ], true );
									if( is_array( $value ) ) {
										return $value;
									}
								}
							}
							return false;
						}, $metadata_collection );
						return array_filter( $metadata );
					}
				}
			}
		}
		return false;
	}

	public static function getHoldOrderMinutes() {
		$minutes = WordPress::get_option( 'woocommerce_hold_stock_minutes' );
		if( isset( $minutes ) ) {
			$minutes = intval( $minutes );
			if( $minutes > 0 ) {
				return $minutes;
			}
		}
		return 0;
	}

	public static function getExpiryNow() {
		$delay_minutes = static::getHoldOrderMinutes();
		if( $delay_minutes > 0 ) {
			$time = strtotime( '+' . $delay_minutes . ' minutes' );
			return date( 'c', $time );
		}
		return false;
	}
	
	// WEBHOOK FUNCTIONS
	public static function newApiCallInstance() {
		$mode = static::ensureGatewayMode();
		$gateway = false;
		if( $mode == BBMSL_SDK::MODE_PRODUCTION ) {
			$gateway = new BBMSL_SDK();
			$gateway->setGatewayMode( BBMSL_SDK::MODE_PRODUCTION );
			$gateway->setMerchantID( WordPress::get_option( static::PARAM_PRODUCTION_MERCHANT_ID ) );
			$gateway->setPublicKey( WordPress::get_option( static::PARAM_PRODUCTION_PUBLIC_KEY, '' ) );
			$gateway->setPrivateKey( WordPress::get_option( static::PARAM_PRODUCTION_PRIVATE_KEY, '' ) );
		}else if( $mode == BBMSL_SDK::MODE_TESTING ) {
			$gateway = new BBMSL_SDK();
			$gateway->setGatewayMode( BBMSL_SDK::MODE_TESTING );
			$gateway->setMerchantID( WordPress::get_option( static::PARAM_TESTING_MERCHANT_ID ) );
			$gateway->setPublicKey( WordPress::get_option( static::PARAM_TESTING_PUBLIC_KEY, '' ) );
			$gateway->setPrivateKey( WordPress::get_option( static::PARAM_TESTING_PRIVATE_KEY, '' ) );
		}
		return $gateway;
	}

	public static function statusVoidable( string $order_status = '' ) {
		return in_array( $order_status, array( 'OPEN', 'SUCCESS' ), true );
	}

	public static function statusRefundable( string $order_status = '' ) {
		return in_array( $order_status, array( 'SETTLED' ), true );
	}
}
<?php

/**
 * class-payment-gateway.php
 *
 * WordPress payment gateway instance file
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\PaymentGateway
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.8
 * @since      File available since initial Release.
 * @deprecated -
 */

namespace BBMSL\Sdk;

use \WC_Order_Item_Product;
use \WC_Payment_Gateway;
use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\Webhook;
use BBMSL\Sdk\WordPress;
use BBMSL\Sdk\Notice;
use BBMSL\Sdk\Setup;
use Automattic\WooCommerce\Admin\Overrides\Order;

class PaymentGateway extends WC_Payment_Gateway
{	
	public function __construct() {
		$this->id					= 'bbmsl';
		$this->icon					= apply_filters( 'woocommerce_bbmsl_icon', BBMSL::getLogoURL( true ) );
		$this->has_fields			= false;
		$this->method_title			= __( 'BBMSL', 'bbmsl-gateway' );
		$this->method_description	= static::getDescription();
		$this->supports[] = 'refunds';
		
		// Load the settings.
		$this->init_form_fields();
		$this->init_settings();
		
		// Define user set variables
		$this->title		= WordPress::get_option( BBMSL::PARAM_GATEWAY_DISPLAY_NAME, $this->method_title );
		$this->description	= WordPress::get_option( BBMSL::PARAM_GATEWAY_DESCRIPTION, $this->method_description, true).Setup::showMethodLogosHTML();
		
		// Actions
		add_action( 'woocommerce_update_options_payment_gateways_'.$this->id, array( $this, 'process_admin_options' ) );
		add_action( 'woocommerce_thankyou_' . $this->id, array( Setup::class, 'setupThankYouPage' ) );
	}
	
	public static function getDescription() {
		return __( 'One-stop online payment solution for Hong Kong merchants. Supports Visa, Master, AMEX, Alipay HK, Alipay CN, Wechat Pay, Apple Pay, Google Pay.', 'bbmsl-gateway' );
	}

	public function generate_settings_html( $form_fields = array(), $echo = true) {
		if( $ev_payload = BBMSL::getRawPostedPayloads() ) {
			if( isset( $ev_payload ) && is_array( $ev_payload ) && sizeof( $ev_payload ) > 0 ) {
				Setup::updatePaymentGatewaySettings( $ev_payload );
			}
		}else{
			Setup::testModeSettings( BBMSL::ensureGatewayMode() );
		}
		Setup::view( 'woocommerce_payments_settings' );
	}
	
	public function process_payment( $order_id ) {
		if( $order = BBMSL::getOrder( $order_id ) ) {

			// check order currency
			$currency_validation = false;
			if( method_exists( $order, 'get_currency' ) ) {
				$currency = $order->get_currency();
				if( !$currency_validation = BBMSL::isAcceptedCurrency( $currency ) ) {
					throw new \Exception( sprintf( esc_attr__( 'Currency %s is not accpeted by this gateway.', 'bbmsl-gateway' ), $currency) );
				}
			}
			if( !$currency_validation) {
				throw new \Exception( esc_attr__( 'Failed to validate order currency.', 'bbmsl-gateway' ) );
			}

			// process order line items
			$line_items = BBMSL::getOrderLineItems( $order );
			if( isset( $line_items ) && is_array( $line_items ) && sizeof( $line_items ) == 0) {
				throw new \Exception( esc_attr__( 'Cart items cannot be added to payment order. Please try refreshing to see if the existing cart is still vaild for checkout.', 'bbmsl-gateway' ) );
			}

			// build callback links settings
			if( method_exists( $this, 'get_return_url' ) ) {
				$success_url = $this->get_return_url( $order );
			}

			// set a proper return cancel URL
			$callback_urls = array();
			if( isset( $success_url ) && is_string( $success_url ) ) {
				$success_url = trim( $success_url );
				if( strlen( $success_url ) > 0 ) {
					$callback_urls[ 'success' ] = $success_url;
				}
			}

			// set a proper fallback cancel URL
			if( ( $fallback_url = BBMSL::getFallbackUrl() ) && isset( $fallback_url ) && is_string( $fallback_url ) ) {
				$fallback_url = trim( $fallback_url );
				if( strlen( $fallback_url ) > 0 ) {
					$callback_urls[ 'fail' ] = $fallback_url;
					$callback_urls[ 'cancel' ] = $fallback_url;
				}
			}
			
			// set a proper webhook URL
			$webhook_url = Webhook::getWebhookUrl();
			if( isset( $webhook_url ) && is_string( $webhook_url ) ) {
				$webhook_url = trim( $webhook_url );
				if( strlen( $webhook_url ) > 0 ) {
					$callback_urls[ 'notify' ] = $webhook_url;
				}
			}
			
			$merchant_id = BBMSL::getMerchantID();
			$merchant_reference = $order->get_order_key();
			$payload = array(
				'merchantId'		=> $merchant_id,
				'amount'			=> $order->get_total(),
				'merchantReference'	=> $merchant_reference,
				'callbackUrl'		=> $callback_urls,
				'isRecurring'		=> 0,
				'lineItems'			=> $line_items,
				'showLang'			=> boolval( 1 === WordPress::get_option( BBMSL::PARAM_SHOW_LANGUAGE_TOOLS, 1 ) ),
				'showPoweredBy'		=> boolval( 1 === WordPress::get_option( BBMSL::PARAM_SHOW_GATEWAY_BRAND, 1 ) ),
			);

			// adjust methods
			$compiled_methods = BBMSL::getCompiledMethods();
			if( isset( $compiled_methods ) && is_string( $compiled_methods ) && strlen( $compiled_methods ) > 0 ) {
				$payload[ 'paymentMethods' ] = $compiled_methods;
			} 

			if( $expiry = BBMSL::getExpiryNow() ) {
				$payload[ 'expiryTime' ] = $expiry;
			}else{
				$payload[ 'expiryTime' ] = date( 'c', strtotime( '+24 hours' ) );
			}
			
			// start bbmsl flow
			if( $gateway = BBMSL::newApiCallInstance() ) {
				$result = $gateway->makeRequest( 'POST', 'hosted-checkout/create/', array(), $payload );
				if( isset( $result ) && is_array( $result ) && sizeof( $result ) > 0 ) {
					$order->update_status( 'on-hold', esc_attr__( 'Awaiting gateway process', 'bbmsl-gateway' ) );
					$order_id = $order->get_id();
					if( function_exists( 'update_post_meta' ) ) {
						update_post_meta( $order_id, BBMSL::META_ORDERING_MODE, $gateway->getModeCode() );
						update_post_meta( $order_id, BBMSL::META_MERCHANT_ID, $merchant_id );
						update_post_meta( $order_id, BBMSL::META_MERCHANT_REF, $merchant_reference );
						update_post_meta( $order_id, BBMSL::META_ORDER_ID, $order_id );
						update_post_meta( $order_id, BBMSL::META_CREATE_ORDER, json_encode( $result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
					}
					if( function_exists( 'add_post_meta' ) ) {
						add_post_meta( $order_id, BBMSL::META_LAST_WEBHOOK, json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
					}
					if( isset( $result[ 'message' ] ) && is_string( $result[ 'message' ] ) ) {
						$error_message = trim( $result[ 'message' ] );
						if( 'success' !== strtolower( $error_message ) ) {
							if( strlen( $error_message ) > 0 ) {
								$error_message_compare = strtolower( trim( $error_message ) );
								if( 'no public key' == $error_message_compare ) {
									throw new \Exception( esc_attr__( '[Error - No Public Key] For further assist, please contact customer service.', 'bbmsl-gateway' ) );
								}else if( $error_message_compare == 'invalid signature' ) {
									throw new \Exception( esc_attr__( '[Error - Invalid Key] For further assist, please contact customer service.', 'bbmsl-gateway' ) );
								}
								throw new \Exception( sprintf( esc_attr__( 'Gateway Error: %s', 'bbmsl-gateway' ), $error_message) );
							}
							throw new \Exception( esc_attr__( 'An error has occured.', 'bbmsl-gateway' ) );
						}
					}
					if( isset( $result[ 'checkoutUrl' ] ) ) {
						$success_url = trim( $result[ 'checkoutUrl' ] );
						update_post_meta( $order_id, BBMSL::META_CHECKOUT_LINK, $success_url );
						wc_reduce_stock_levels( $order );
						wc_empty_cart();
						return array(
							'result' 	=> 'success',
							'redirect'	=> $success_url,
						);
					}
				}
				throw new \Exception( esc_attr__( 'Failed to obtain checkout session.', 'bbmsl-gateway' ) );
			}
			throw new \Exception( esc_attr__( 'Failed initiate gateway.', 'bbmsl-gateway' ) );
		}
		throw new \Exception( esc_attr__( 'Failed to get order instance.', 'bbmsl-gateway' ) );
	}

	public function process_refund( $order_id, $amount = null, $reason = '' ) {
		if( isset( $order_id ) && is_numeric( $order_id ) ) {
			$order_id = intval( $order_id );
			if( $order_id > 0 ) {
				$metadata = BBMSL::getOrderMetaByID( $order_id );
				if( isset( $metadata ) && is_array( $metadata ) && sizeof( $metadata ) > 0 ) {
					$bbmsl_order_id = BBMSL::getGatewayOrderID( $metadata );
					$bbmsl_merchant_reference = BBMSL::getMerchantReference( $metadata );
					if( isset( $amount ) && floatval( $amount ) > 0 ) {
						if( $gateway = BBMSL::newApiCallInstance() ) {
							$query_result = $gateway->refundOrder( $bbmsl_order_id, $bbmsl_merchant_reference, $amount );
							if( isset( $query_result ) && is_array( $query_result ) && sizeof( $query_result ) > 0 ) {
								if( function_exists( 'add_post_meta' ) ) {
									add_post_meta( $order_id, BBMSL::META_LAST_WEBHOOK, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
								}
								if( isset( $query_result[ 'message' ] ) ) {
									$api_message = wp_strip_all_tags( $query_result[ 'message' ] );
									throw new \Exception( esc_attr__( $api_message, 'bbmsl-gateway' ) );
									return false;
								}
							}
							return true;
						}
					}
				}
			}
		}
		return false;
	}
}
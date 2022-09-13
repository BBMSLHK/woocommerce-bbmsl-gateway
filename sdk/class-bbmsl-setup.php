<?php

/**
 * class-bbmsl-setup.php
 *
 * WordPress setup library file
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\Setup
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.8
 * @since      File available since initial Release.
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\BBMSL_SDK;
use BBMSL\Sdk\WordPress;
use BBMSL\Sdk\Notice;
use BBMSL\Sdk\Utility;
use BBMSL\Sdk\PaymentGateway;

class Setup
{
	public static function setupLink() {
		if( function_exists( 'admin_url' ) ) {
			return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=bbmsl' );
		}
		return false;
	}

	public static function supportLink() {
		return apply_filters( 'bbmsl_support_url', 'https://bbmsl.com/tc/contact/' );
	}

	public static function view( string $view_file = '', array $params = array() ) {
		if( isset( $view_file ) && is_string( $view_file ) ) {
			$view_file = trim( $view_file );
			if( ! empty( $view_file ) ) {
				$realpath = realpath( implode( DIRECTORY_SEPARATOR, array( BBMSL_PLUGIN_DIR, 'views', $view_file . '.php' ) ) );
				if( 0 === stripos( $realpath, BBMSL_PLUGIN_DIR ) && file_exists( $realpath ) && !is_dir( $realpath ) && filesize( $realpath ) > 0 ) {
					extract( $params, EXTR_SKIP );
					Notice::showErrors();
					return include( $realpath );
				}
			}
		}
		return false;
	}

	public static function setupPluginGatewayTitle( string $title = '', string $payment_id = '' ) {
		if( 'bbmsl' === $payment_id ) {
			return WordPress::get_option( BBMSL::PARAM_GATEWAY_DISPLAY_NAME );
		}
		return $title;
	}

	public static function setupPluginGatewayDescription( string $description = '', string $payment_id = '' ) {
		if( 'bbmsl' === $payment_id ) {
			return WordPress::get_option( BBMSL::PARAM_GATEWAY_DESCRIPTION, $description, true );
		}
		return $description;
	}

	public static function setupPluginGatewayIcon( string $icon = '', string $payment_id = '' ) {
		if( 'bbmsl' === $payment_id ) {
			return BBMSL::getMethodLogoHTML();
		}
		return $icon;
	}

	public static function testModeSettings( string $checking_mode = '' ) {
		if( isset( $checking_mode ) && is_string( $checking_mode ) ) {
			$checking_mode = trim( $checking_mode );
			if( strlen( $checking_mode ) > 0 && BBMSL_SDK::isModeAccepted( $checking_mode ) ) {
				$mode = $checking_mode;
			}else{
				Notice::addError( Notice::TYPE_ERROR, sprintf( esc_attr__( 'Mode (%s) is not accepted, please try again.', 'bbmsl-gateway' ), $mode ) );
			}
		}
		
		if( $mode === BBMSL_SDK::MODE_PRODUCTION ) {
			$ev_merchant_id = WordPress::get_option( BBMSL::PARAM_PRODUCTION_MERCHANT_ID );
			if( isset( $ev_merchant_id ) ) {
				if ( Utility::isInt( $ev_merchant_id ) ) {
					$merchant_id = intval( $ev_merchant_id );
					if( $merchant_id <= 0 ) {
						Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Production merchant ID must larger than 0.', 'bbmsl-gateway' ) );
					}
				} else {
					Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Production merchant ID must be an integer.', 'bbmsl-gateway' ) );
				}
			} else {
				Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Production merchant ID cannot be blank.', 'bbmsl-gateway' ) );
			}
			
			if( !( BBMSL_SDK::check_key( WordPress::get_option( BBMSL::PARAM_PRODUCTION_PUBLIC_KEY ) ) &&
				BBMSL_SDK::check_key( WordPress::get_option( BBMSL::PARAM_PRODUCTION_PRIVATE_KEY ) ) ) ) {
				Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Production keypair not ready, please regenerate and upload to BBMSL Portal.', 'bbmsl-gateway' ) );
			}

			unset( $merchant_id );
			unset( $ev_merchant_id );

		} else if( $mode === BBMSL_SDK::MODE_TESTING ) {
			$ev_merchant_id = WordPress::get_option( BBMSL::PARAM_TESTING_MERCHANT_ID );
			if( isset( $ev_merchant_id ) ) {
				if ( Utility::isInt( $ev_merchant_id ) ) {
					$merchant_id = intval( $ev_merchant_id );
					if( $merchant_id <= 0 ) {
						Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Testing merchant ID must larger than 0.', 'bbmsl-gateway' ) );
					}
				} else {
					Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Testing merchant ID must be an integer.', 'bbmsl-gateway' ) );
				}
			} else {
				Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Testing merchant ID cannot be blank.', 'bbmsl-gateway' ) );
			}
			
			if( !( BBMSL_SDK::check_key( WordPress::get_option( BBMSL::PARAM_TESTING_PUBLIC_KEY ) ) &&
				BBMSL_SDK::check_key( WordPress::get_option( BBMSL::PARAM_TESTING_PRIVATE_KEY ) ) ) ) {
				Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Testing keypair not ready, please regenerate and upload to BBMSL Portal.', 'bbmsl-gateway' ) );
			}

			unset( $merchant_id );
			unset( $ev_merchant_id );
		}
		
		return !!! Notice::hasError();
	}

	public static function updatePaymentGatewaySettings( array $ev_payload = array() ) {
		if( WordPress::currentScreen( 'woocommerce_page_wc-settings' ) ) {
			$process = true;
			if( isset( $ev_payload[ 'action' ]  ) && is_string( $ev_payload[ 'action' ] ) ) {
				$target_action = strtolower( trim( $ev_payload[ 'action' ] ) );
				if( in_array( $target_action, array(
					BBMSL::ACTION_REGEN_PRODUCTION_KEYS,
					BBMSL::ACTION_REGEN_TESTING_KEYS,
				), true) ) {
					$keypair = BBMSL_SDK::newKeyPair();
					if( isset( $keypair ) && is_array( $keypair ) && sizeof( $keypair ) == 2 &&
					isset( $keypair[ 'public' ] ) && is_string( $keypair[ 'public' ] ) &&
					isset( $keypair[ 'private' ] ) && is_string( $keypair[ 'private' ] ) ) {
						$public_key		= BBMSL_SDK::pem2str( $keypair[ 'public' ] );
						$private_key	= BBMSL_SDK::pem2str( $keypair[ 'private' ] );
						if( BBMSL_SDK::check_key( $public_key ) && BBMSL_SDK::check_key( $private_key ) ) {
							if( $target_action === BBMSL::ACTION_REGEN_TESTING_KEYS ) {
								WordPress::update_option( BBMSL::PARAM_TESTING_PUBLIC_KEY, $public_key );
								WordPress::update_option( BBMSL::PARAM_TESTING_PRIVATE_KEY, $private_key );
								WordPress::update_option( BBMSL::PARAM_TESTING_KEY_LAST_UPDATE, date( 'Y-m-d H:i:s' ) );
								Notice::displayNewKeypairNotice( BBMSL_SDK::MODE_TESTING );
							}else if( $target_action === BBMSL::ACTION_REGEN_PRODUCTION_KEYS ) {
								WordPress::update_option( BBMSL::PARAM_PRODUCTION_PUBLIC_KEY, $public_key );
								WordPress::update_option( BBMSL::PARAM_PRODUCTION_PRIVATE_KEY, $private_key );
								WordPress::update_option( BBMSL::PARAM_PRODUCTION_KEY_LAST_UPDATE, date( 'Y-m-d H:i:s' ) );
								Notice::displayNewKeypairNotice( BBMSL_SDK::MODE_PRODUCTION );
							}
							$process = false;
						} else {
							Notice::flash( esc_attr__( 'Generated key check failed, please try again.', 'bbmsl-gateway' ), Notice::TYPE_WARNING, true );
						}
					}
				}
			}
			if( $process && isset( $ev_payload[ 'payment_settings' ] ) && is_array( $ev_payload[ 'payment_settings' ] ) && sizeof( $ev_payload[ 'payment_settings' ] ) > 0 ) {
				$ev_settings = $ev_payload[ 'payment_settings' ];
	
				if( isset( $ev_settings[ BBMSL::PARAM_GATEWAY_DISPLAY_NAME] ) && is_string( $ev_settings[ BBMSL::PARAM_GATEWAY_DISPLAY_NAME] ) ) {
					$display_name = trim( $ev_settings[ BBMSL::PARAM_GATEWAY_DISPLAY_NAME] );
					if( ! empty( $display_name ) ) {
						WordPress::update_option( BBMSL::PARAM_GATEWAY_DISPLAY_NAME, WordPress::plaintext( $display_name, false ) );
					}
				}

				if( isset( $ev_settings[ BBMSL::PARAM_GATEWAY_MODE] ) && is_string( $ev_settings[ BBMSL::PARAM_GATEWAY_MODE] ) ) {
					$mode = strtolower( trim( $ev_settings[ BBMSL::PARAM_GATEWAY_MODE] ) );
					if( ! empty( $mode ) && BBMSL_SDK::isModeAccepted( $mode ) ) {
						$mode_pass = static::testModeSettings( $mode );
						if( $mode_pass ) {
							WordPress::update_option( BBMSL::PARAM_GATEWAY_MODE, $mode );
						} else {
							Notice::addError( Notice::TYPE_ERROR, sprintf( esc_attr__( 'Mode (%s) setting not ready, please try again.', 'bbmsl-gateway' ), $mode ) );
						}
					}else{
						Notice::addError( Notice::TYPE_ERROR, sprintf( esc_attr__( 'Mode(%s) is not accepted, please try again.', 'bbmsl-gateway' ), $mode ) );
					}
				}else{
					Notice::addError( Notice::TYPE_ERROR, esc_attr__( 'Mode is not defined, please try again.', 'bbmsl-gateway' ) );
				}
				
				if( isset( $ev_settings[ BBMSL::PARAM_PRODUCTION_MERCHANT_ID ] ) ) {
					if ( Utility::isInt( $ev_settings[ BBMSL::PARAM_PRODUCTION_MERCHANT_ID ] ) ) {
						$merchant_id = intval( $ev_settings[ BBMSL::PARAM_PRODUCTION_MERCHANT_ID ] );
						if( $merchant_id > 0 ) {
							WordPress::update_option( BBMSL::PARAM_PRODUCTION_MERCHANT_ID, $merchant_id );
						} else {
							Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Production merchant ID must larger than 0.', 'bbmsl-gateway' ) );
						}
					} else {
						Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Production merchant ID must be an integer.', 'bbmsl-gateway' ) );
					}
				} else {
					Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Production merchant ID cannot be blank.', 'bbmsl-gateway' ) );
				}

				if( isset( $ev_settings[ BBMSL::PARAM_TESTING_MERCHANT_ID ] ) ) { 
					if( Utility::isInt( $ev_settings[ BBMSL::PARAM_TESTING_MERCHANT_ID ] ) ) {
						$merchant_id = intval( $ev_settings[ BBMSL::PARAM_TESTING_MERCHANT_ID ] );
						if( $merchant_id > 0 ) {
							WordPress::update_option( BBMSL::PARAM_TESTING_MERCHANT_ID, $merchant_id );
						} else {
							Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Testing merchant ID must larger than 0.', 'bbmsl-gateway' ) );
						}
					} else {
						Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Testing merchant ID must be an integer.', 'bbmsl-gateway' ) );
					}
				} else {
					Notice::addError( Notice::TYPE_WARNING, esc_attr__( 'Testing merchant ID cannot be blank.', 'bbmsl-gateway' ) );
				}
	
				if( isset( $ev_settings[ BBMSL::PARAM_GATEWAY_METHODS ] ) && is_array( $ev_settings[ BBMSL::PARAM_GATEWAY_METHODS ] ) ) {
					$selected = array_intersect( array_values( $ev_settings[ BBMSL::PARAM_GATEWAY_METHODS ] ), array_keys( BBMSL::getMethods() ) );
					if( sizeof( $selected ) > 0 ) {
						WordPress::update_option( BBMSL::PARAM_GATEWAY_METHODS, json_encode( $selected ) );
					}else{
						WordPress::update_option( BBMSL::PARAM_GATEWAY_METHODS, '[]' );
					}
				}else{
					WordPress::update_option( BBMSL::PARAM_GATEWAY_METHODS, '[]' );
				}
	
				if( isset( $ev_settings[ BBMSL::PARAM_GATEWAY_REFUND ] ) && is_string( $ev_settings[ BBMSL::PARAM_GATEWAY_REFUND ] ) ) {
					$boolean = intval( $ev_settings[ BBMSL::PARAM_GATEWAY_REFUND ] );
					WordPress::update_option( BBMSL::PARAM_GATEWAY_REFUND, ( $boolean > 0 ? 1 : 0).'' );
				}
	
				if( isset( $ev_settings[ BBMSL::PARAM_GATEWAY_DESCRIPTION ] ) && is_string( $ev_settings[ BBMSL::PARAM_GATEWAY_DESCRIPTION ] ) ) {
					$description = trim( $ev_settings[ BBMSL::PARAM_GATEWAY_DESCRIPTION ] );
					if( ! empty( $description ) ) {
						WordPress::update_option( BBMSL::PARAM_GATEWAY_DESCRIPTION, WordPress::richtext( $description ) );
					}
				}
	
				if( isset( $ev_settings[ BBMSL::PARAM_GATEWAY_THANK_YOU_PAGE ] ) && is_string( $ev_settings[ BBMSL::PARAM_GATEWAY_THANK_YOU_PAGE ] ) ) {
					$thank_you_content = trim( $ev_settings[ BBMSL::PARAM_GATEWAY_THANK_YOU_PAGE ] );
					if( ! empty( $thank_you_content ) ) {
						WordPress::update_option( BBMSL::PARAM_GATEWAY_THANK_YOU_PAGE, WordPress::richtext( $thank_you_content ) );
					}
				}
	
				// if( isset( $ev_settings[ BBMSL::PARAM_GATEWAY_INSTRUCTION] ) && is_string( $ev_settings[ BBMSL::PARAM_GATEWAY_INSTRUCTION] ) ) {
				// 	$instruction = trim( $ev_settings[ BBMSL::PARAM_GATEWAY_INSTRUCTION] );
				// 	if( ! empty( $instruction ) ) {
				// 		WordPress::update_option( BBMSL::PARAM_GATEWAY_INSTRUCTION, WordPress::richtext( $instruction ) );
				// 	}
				// }
	
				if( isset( $ev_settings[ BBMSL::PARAM_GATEWAY_EMAIL_CONTENT ] ) && is_string( $ev_settings[ BBMSL::PARAM_GATEWAY_EMAIL_CONTENT ] ) ) {
					$email_content = trim( $ev_settings[ BBMSL::PARAM_GATEWAY_EMAIL_CONTENT ] );
					if( ! empty( $email_content ) ) {
						WordPress::update_option( BBMSL::PARAM_GATEWAY_EMAIL_CONTENT, WordPress::richtext( $email_content ) );
					}
				}
	
				if( isset( $ev_settings[ BBMSL::PARAM_EXPRESS_CHECKOUT ] ) && is_string( $ev_settings[ BBMSL::PARAM_EXPRESS_CHECKOUT ] ) ) {
					$boolean = intval( $ev_settings[ BBMSL::PARAM_EXPRESS_CHECKOUT ] );
					WordPress::update_option( BBMSL::PARAM_EXPRESS_CHECKOUT, ( $boolean > 0 ? 'true' : 'false' ) );
				}
			}
			return true;
		}
		return false;
	}

	public static function showMethodLogosHTML() {
		$image_html = array();
		foreach( BBMSL::getCoeasedMethods() as $key => $method) {
			if( BBMSL::hasSelectedMethod( $key ) ) {
				$image_html[] = sprintf( '<img class="logo" src="%s" />', plugin_dir_url( BBMSL_PLUGIN_FILE ) . $method[ 'logo' ] );
			}
			$imgae_html[] = '|';
		}
		return sprintf( '<div class="bbmsl_payment_methods">%s</div>', implode( $image_html ) );
	}

	public static function setupPluginActionLinks( array $links = array(), string $plugin_file = '' ) {
		$action_links = array();
		if( $settings_link = static::setupLink() ) {
			$action_links[ 'settings' ] = sprintf( '<a href="%s" aria-label="%s">%s</a>', 
				$settings_link,
				esc_attr__( 'View BBMSL settings', 'bbmsl-gateway' ),
				esc_html__( 'Settings', 'bbmsl-gateway' )
			);
		}
		return array_merge( $action_links, $links );
	}

	public static function setupPluginMeta( array $links = array(), string $file = '' ) {
		$row_meta = [
			'apidocs' => sprintf( '<a target="_blank" rel="noreferrer noopener" href="%s" aria-label="%s">%s</a>',
				esc_url( apply_filters( 'bbmsl_apidocs_url', 'https://docs.bbmsl.com/' ) ),
				esc_attr__( 'View BBMSL API docs', 'woocommerce' ),
				esc_html__( 'API docs', 'bbmsl-gateway' )
			),
			'support' => sprintf( '<a target="_blank" rel="noreferrer noopener" href="%s" aria-label="%s">%s</a>',
				esc_url( static::supportLink() ),
				esc_attr__( 'Contact Support', 'bbmsl-gateway' ),
				esc_html__( 'Contact Support', 'bbmsl-gateway' )
			),
		];
		return array_merge( $links, $row_meta );
	}

	public static function setupPluginAddGateway( array $gateways = array() ) {
		if( function_exists( 'is_admin' ) && is_admin() ? true : BBMSL::ready() ) {	
			array_splice( $gateways, 0, 0, PaymentGateway::class );
		}
		return $gateways;
	}

	public static function setupShoppingCart() {
		if( !BBMSL::ready() ) {
			return;
		}
		echo sprintf( '<a class="button checkout wc-forward bbmsl-btn bbmsl-express-checkout" id="bbmsl_express_checkout" href="%s">%s</a>',
			esc_url( WC()->cart->get_checkout_url() . '?gateway=bbmsl' ),
			esc_attr__( 'BBMSL Checkout', 'bbmsl-gateway' )
		);
	}

	public static function setupExpressCheckoutAesthetics() {
		if( !BBMSL::ready() ) {
			return;
		}
		if( Utility::checkBoolean( WordPress::get_option( BBMSL::PARAM_EXPRESS_CHECKOUT ) ) ) {
			echo '<style>#bbmsl_express_checkout{display:block!important;}</style>';
		}else{
			echo '<style>#bbmsl_express_checkout{display:none!important;}</style>';
		}
	}

	public static function setupOrderDetails() {
		if( WordPress::currentScreen( 'shop_order' ) ) {

			// obtain order info
			if( function_exists( 'get_the_ID' ) ) {
				$order_id	= intval( get_the_ID() );
				$metadata	= BBMSL::getOrderMetaByID( $order_id );
				$order		= BBMSL::getOrder( $order_id . '' );
			};

			// check order payment method to be of our gateway before proceeding
			$order_payment_method = 'unknown';
			if( method_exists( $order, 'get_payment_method' ) ) {
				$order_payment_method = $order->get_payment_method();
			}
			if( !( isset( $order_payment_method ) && is_string( $order_payment_method ) ) ) {
				$order_payment_method = strtolower( trim( $order_payment_method ) );
				if( $order_payment_method !== 'bbmsl' ) {
					return;
				}
			}

			// process the order for fetching API info
			$error = false;
			$query_result	= '';
			$order_info		= '';
			$order_status	= '';
			$bbmsl_merchant_reference = BBMSL::getMerchantReferenceByID( $order_id );
			$portal_link = BBMSL::TESTING_PORTAL_LINK;
			if( BBMSL::ready() && $gateway = BBMSL::newApiCallInstance() ) {
				$portal_link = $gateway->getPortalLink();
				if( ! empty( $bbmsl_merchant_reference ) ) {
					$query_result = $gateway->queryOrder( $bbmsl_merchant_reference );
					update_post_meta( $order_id, BBMSL::META_LAST_QUERY, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
					if( isset( $query_result ) && is_array( $query_result ) && sizeof( $query_result ) > 0 ) {
						if( isset( $query_result[ 'message' ] ) && is_string( $query_result[ 'message' ] ) ) {
							$api_message = strtolower( trim( $query_result[ 'message' ] ) );
							if( ! empty( $api_message ) ) {
								if( 'success' == $api_message ) {
									if( isset( $query_result[ 'order' ] ) && is_array( $query_result[ 'order' ] ) && sizeof( $query_result[ 'order' ] ) > 0 ) {
										$order_info = $query_result[ 'order' ];
										if( isset( $order_info[ 'status' ] ) ) {
											$order_status = strtoupper( trim( $order_info[ 'status' ] ) );
										}
									}
								}else{
									$error = esc_attr__( sprintf( '[API Message] %s', $api_message ), 'bbmsl-gateway' );
								}
							}
						}
					}else{
						$error = esc_attr__( 'Query returned empty result.', 'bbmsl-gateway' );
					}
				}else{
					$error = esc_attr__( 'Empty order ID or merchant reference.', 'bbmsl-gateway' );
				}
			}else{
				$error = esc_attr__( 'Cannot create new API call instance.', 'bbmsl-gateway' );
			}
			add_action( 'submitpost_box', function() use(
				$query_result,
				$order_id,
				$order_info,
				$order_status,
				$error,
				$metadata,
				$portal_link
			) {
				return static::view( 'woocommerce_order_status', array(
					'query_result'		=> $query_result,
					'order_id'			=> $order_id,
					'order_info'		=> $order_info,
					'order_status'		=> $order_status,
					'error'				=> $error,
					'metadata'			=> $metadata,
					'portal_link'		=> $portal_link,
				) );
			} );
		}
	}

	public static function setupRefundHandling( $refund ) {
		$ev_posted = array_intersect_key( $_POST, array(
			'action'					=> null,
			'order_id'					=> null,
			'refund_amount'				=> null,
			'refunded_amount'			=> null,
			'refund_reason'				=> null,
			'line_item_qtys'			=> null,
			'line_item_totals'			=> null,
			'line_item_tax_totals'		=> null,
			'api_refund'				=> null,
			'restock_refunded_items'	=> null,
			'security'					=> null,
		) );
		if( isset( $ev_posted ) && is_array( $ev_posted ) && sizeof( $ev_posted ) > 0 ) {
			if( isset( $ev_posted[ 'action' ] ) && is_string( $ev_posted[ 'action' ] ) ) {
				$action = strtolower( trim( $ev_posted[ 'action' ] ) );
				if( 'woocommerce_refund_line_items' != $action ) {
					return;
				}
			}
			
			if( isset( $ev_posted[ 'api_refund' ] ) && is_string( $ev_posted[ 'api_refund' ] ) ) {
				$api_refund = strtolower( trim( $ev_posted[ 'api_refund' ] ) );
				if( 'true' != $api_refund ) {
					return;
				}
			}
			
			if( isset( $ev_posted[ 'order_id' ] ) && is_numeric( $ev_posted[ 'order_id' ] ) ) {
				$order_id = intval( $ev_posted[ 'order_id' ] );
				$order = BBMSL::getOrder( $order_id.'' );
				if( method_exists( $order, 'get_payment_method' ) && 'bbmsl' === $order->get_payment_method() ) {	
					if( method_exists( $order, 'get_total_refunded' ) && $order->get_total_refunded() > 0 ) {
						$metadata	= BBMSL::getOrderMetaByID( $order_id );
					}
					if( isset( $metadata ) && is_array( $metadata ) && sizeof( $metadata ) > 0 ) {
						$bbmsl_merchant_reference = BBMSL::getMerchantReference( $metadata );
						if( empty( $bbmsl_merchant_reference ) ) {
							Notice::flash( esc_attr__( 'Failed to obtain order metadata.', 'bbmsl-gateway' ), Notice::TYPE_ERROR, true );
						}
						if( $gateway = BBMSL::newApiCallInstance() ) {
							$query_result = $gateway->queryOrder( $bbmsl_merchant_reference );
							update_post_meta( $order_id, BBMSL::META_LAST_QUERY, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
							if( isset( $query_result ) && is_array( $query_result ) && sizeof( $query_result ) > 0 ) {
								if( isset( $query_result[ 'message' ] ) && is_string( $query_result[ 'message' ] ) ) {
									$api_message = strtolower( trim( $query_result[ 'message' ] ) );
									if( ! empty( $api_message ) ) {
										if( 'success' == $api_message) {
											if( isset( $query_result[ 'order' ] ) && is_array( $query_result[ 'order' ] ) && sizeof( $query_result[ 'order' ] ) > 0 ) {
												$order_info = $query_result[ 'order' ];
												if( isset( $order_info[ 'status' ] ) ) {
													$order_status = strtoupper( trim( $order_info[ 'status' ] ) );
												}
											}
										}
									}
								}
							}
						}
						if( !BBMSL::statusRefundable( $order_status ) ) {
							if( BBMSL::statusVoidable( $order_status ) ) {
								Notice::flash( esc_attr__( 'Refund only works for settled orders, use VOID function instead.', 'bbmsl_gateway' ), Notice::TYPE_ERROR, true );
							}
							return false;
						}
						Notice::flash( esc_attr__( 'This order has already been refunded before.', 'bbmsl-gateway' ), Notice::TYPE_ERROR, true );
						return false;
					}
				}
			}
		}
		return true;
	}

	public static function setupVoidHandling( int $order_id = 0 ) {
		if( !WordPress::currentScreen( 'shop_order' ) ) {
			return false;
		}

		$params = BBMSL::getRawPostedPayloads();

		// check if there are any posted parameters.
		if( !( isset( $params ) && is_array( $params ) && sizeof( $params ) > 0 ) ) {
			return;
		}

		// check if a bbmsl_action is specified.
		if( isset( $params[ 'bbmsl_action' ] ) && is_string( $params[ 'bbmsl_action' ] ) ) {
			$bbmsl_action = strtolower( trim( $params[ 'bbmsl_action' ] ) );
			if( 'void_order' !== $bbmsl_action ) {
				return;
			}
		}else{
			return;
		}

		// evade woocommerce normal order update operations.
		if( isset( $_POST[ 'save' ] ) && is_string( $_POST[ 'save' ] ) ) {
			$default_submit = strtolower( trim( $_POST[ 'save' ] ) );
			if( 'update' === $default_submit ) {
				return;
			}
		}

		// actually do the saving
		if( isset( $params ) && is_array( $params ) && sizeof( $params ) > 0 ) {
			if( isset( $params[ 'order_reference' ] ) && is_string( $params[ 'order_reference' ] ) ) {
				$order_reference = trim( $params[ 'order_reference' ] );
				$order_id	= intval( BBMSL::getOrderID( $order_reference ) );
				$metadata	= BBMSL::getOrderMetaByID( $order_id );
			}
			if( isset( $metadata ) && is_array( $metadata ) && sizeof( $metadata ) > 0 ) {
				$bbmsl_order_id = BBMSL::getGatewayOrderID( $metadata );
				$bbmsl_merchant_reference = BBMSL::getMerchantReference( $metadata );
				if( empty( $bbmsl_merchant_reference ) ) {
					Notice::flash( esc_attr__( 'Failed to obtain order metadata.', 'bbmsl-gateway' ), Notice::TYPE_ERROR, true );
				}

				// query online order status
				if( $gateway = BBMSL::newApiCallInstance() ) {
					$query_result = $gateway->queryOrder( $bbmsl_merchant_reference );
					update_post_meta( $order_id, BBMSL::META_LAST_QUERY, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
					if( isset( $query_result ) && is_array( $query_result ) && sizeof( $query_result ) > 0 ) {
						if( isset( $query_result[ 'message' ] ) && is_string( $query_result[ 'message' ] ) ) {
							$api_message = strtolower( trim( $query_result[ 'message' ] ) );
							if( ! empty( $api_message ) ) {
								if( 'success' == $api_message) {
									if( isset( $query_result[ 'order' ] ) && is_array( $query_result[ 'order' ] ) && sizeof( $query_result[ 'order' ] ) > 0 ) {
										$order_info = $query_result[ 'order' ];
										if( isset( $order_info[ 'status' ] ) ) {
											$order_status = strtoupper( trim( $order_info[ 'status' ] ) );
										}
									}
								}else{
									Notice::flash( esc_attr__( 'API call failed.', 'bbmsl-gateway' ) . PHP_EOL . print_r( $query_result ), Notice::TYPE_ERROR, true );
								}
							}
						}
					}
				}else{
					Notice::flash( 'Failed to obtain new API instance.', Notice::TYPE_ERROR, true );
				}

				if( isset( $order_status ) && is_string( $order_status ) ) {
					$order_status = strtoupper( trim( $order_status ) );
					if( BBMSL::statusVoidable( $order_status ) ) {
						$query_result = $gateway->voidOrder( $bbmsl_order_id, $bbmsl_merchant_reference );
						update_post_meta( $order_id, BBMSL::META_LAST_VOID, json_encode( $query_result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) );
					}else{
						Notice::flash( sprintf( 'Order status: %s cannot be voided! Use refund function instead.', $order_status ), Notice::TYPE_ERROR, true );
					}
				}
			}
		}
		return true;
	}
	
	public static function setupThankYouPage() {
		echo WordPress::get_option( BBMSL::PARAM_GATEWAY_THANK_YOU_PAGE, '', true );
		return true;
	}

	public static function setupOrderEmailFooter() {
		echo WordPress::get_option( BBMSL::PARAM_GATEWAY_EMAIL_CONTENT, '', true );
		return true;
	}
}
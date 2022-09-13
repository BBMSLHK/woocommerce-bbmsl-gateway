<?php

/**
 * WebhookController.php
 *
 * WordPress Webhook Controller File
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Controllers\WebhookController
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.8
 * @since      File available since initial Release.
 * @deprecated -
 */

namespace BBMSL\Controllers;

use BBMSL\Sdk\BBMSL;
use BBMSL\Sdk\BBMSL_SDK;
use BBMSL\Sdk\Utility;
use BBMSL\Sdk\WordPress;

class WebhookController{

	// WEBHOOK FUNCTIONS	
	private static function simpleLog( string $operation = '', string $request_id = '', array $info = [] ) {
		$base_info = array(
			'type'			=> $operation,
			'request_id'	=> $request_id,
		);
		\BBMSL\BBMSL::putLog( \BBMSL\BBMSL::LOG_TYPE_WEBHOOK, array(
			'validated'	=> false,
			'operation'	=> array_merge( $base_info, $info, $base_info ),
		) );
	}

	public static function notification() {
		$ev_payload = file_get_contents( 'php://input' );
		if( isset( $ev_payload ) && is_string( $ev_payload ) ) {
			$ev_payload = trim( $ev_payload );
			if( strlen( $ev_payload ) > 0 && Utility::isJson( $ev_payload ) ) {
				$payload = json_decode( $ev_payload, true );
			}
		}

		$logging_request_id = 'request_' . bin2hex( random_bytes( 32 ) );
		
		$logging_request_method = '';
		if( isset( $_SERVER[ 'REQUEST_METHOD' ] ) && is_string( $_SERVER[ 'REQUEST_METHOD' ] ) ) {
			$request_method = trim( $_SERVER[ 'REQUEST_METHOD' ] );
			if( strlen( $request_method ) > 0 ) {
				$logging_request_method = esc_html( $request_method );
			}
		}

		\BBMSL\BBMSL::putLog(\BBMSL\BBMSL::LOG_TYPE_WEBHOOK, array(
			'request' => array(
				'id'		=> $logging_request_id,
				'method'	=> $logging_request_method,
				'payload'	=> array(
					'raw'	=> ( isset( $ev_payload ) ? $ev_payload : false ),
					'json'	=> ( isset( $payload ) ? $payload : false ),
				),
			),
		) );

		static::simpleLog( 'init_receive', $logging_request_id, array( 'raw' => $_REQUEST ) );

		if( isset( $payload ) && is_array( $payload ) && sizeof( $payload ) > 0 ) {
			$approved_keys = array(
				'orderId',
				'amount',
				'cardType',
				'status',
				'merchantReference',
				'signature'
			);
			if( sizeof( array_intersect( $approved_keys, array_keys( $payload ) ) ) == sizeof( $approved_keys ) ) {
				
				static::simpleLog( 'approved_keys', $logging_request_id );

				if( is_string( $payload[ 'signature' ] ) ) {
					$signature = trim( $payload[ 'signature' ] );
					if( strlen( $signature ) > 0 ) {
						$data = array_diff_key( $payload, [ 'signature' ] );
						ksort( $data );
						if( isset( $data[ 'signature' ] ) ) {
							unset( $data[ 'signature' ] );
						}
						
						static::simpleLog( 'signature_read', $logging_request_id );

						$query_str = http_build_query( $data );
						if( $gateway = BBMSL::newApiCallInstance() ) {

							static::simpleLog( 'api_instance', $logging_request_id );

							if( $gateway->webhookVerify( $query_str, $signature ) ) {

								static::simpleLog( 'signature_verified', $logging_request_id );

								$order_reference = trim( $payload[ 'merchantReference' ] );

								static::simpleLog( 'order_reference', $logging_request_id, array( 'order_refeerence' => $order_reference ) );
								
								$order_id = BBMSL::getOrderID( $order_reference );

								if( isset( $order_id ) ) {
									$order_id = intval( $order_id );
									if( BBMSL::checkOrderID( $order_id ) ) {
										$order = BBMSL::getOrder( $order_id );
										if( isset( $order ) && ( false !== $order) ) {
								
											static::simpleLog( 'update_order_webhook', $logging_request_id, array( 'order' => $order ) );

											if( method_exists( $order, 'payment_complete' ) ) {
												$order->payment_complete( $order_reference );
											}
											$order->update_status( 'processing', esc_attr__( 'Processing', 'bbmsl-gateway' ) );
											update_post_meta( $order_id, BBMSL::META_LAST_WEBHOOK, json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );
											
											static::simpleLog( 'update_order_webhook_success', $logging_request_id );
											
											return array( 'OK', 200 ); // must be in CAPS!!!
										}else{
											return array( sprintf( esc_attr__( 'Failed to get order %s from eshop storage.', 'bbmsl-gateway' ), $order_id ), 404 );
										}
									}else{
										return array( sprintf( esc_attr__( 'Order(#%s) has been removed.', 'bbmsl-gateway' ), $order_reference ), 404 );
									}
								}
								return array( sprintf( esc_attr__( 'Order(#%s) not found.', 'bbmsl-gateway' ), $order_reference ), 404 );
							}
							return array( sprintf( esc_attr__( 'Invalid signature, validated with %s public key.', 'bbmsl-gateway' ), $gateway->getModeCode() ), 403 );
						}
						return array( esc_attr__( 'Gateway Error', 'bbmsl-gateway' ), 500 );
					}
				}
			}
			if( ! isset( $payload[ 'signature' ] ) ) {
				return array( esc_attr__( 'Signature is missing.', 'bbmsl-gateway' ), 403 );
			}
			return array( esc_attr__( 'Bad request.', 'bbmsl-gateway' ), 403 );
		}
		return array( esc_attr__( 'Hello', 'bbmsl-gateway' ), 404 );
	}
}

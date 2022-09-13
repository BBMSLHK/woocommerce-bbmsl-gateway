<?php

/**
 * class-bbmsl-sdk.php
 *
 * WordPress Payment encryption and key pair generation SDK main file
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\BBMSL_SDK
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.8
 * @since      File available since initial Release.
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

use BBMSL\Sdk\Utility;
use BBMSL\Sdk\WordPress;
use phpseclib3\Crypt\RSA;

class BBMSL_SDK
{
	private const DEFAULT_KEY_SIZE			= 2048;
	public const CERT_TYPE_PKCS1			= 'PKCS1';
	public const CERT_TYPE_PKCS8			= 'PKCS8';

	private const API_TESTING_ENDPOINT		= 'https://payapi.sit.bbmsl.com/';
	private const API_PRODUCTION_ENDPOINT	= 'https://payapi.prod.bbmsl.com/';

	public const MODE_TESTING				= 'sandbox';
	public const MODE_PRODUCTION			= 'production';

	public const PAYMENT_TYPE_ALIPAY		= 'ALIPAY';
	public const PAYMENT_TYPE_ALIPAYHK		= 'ALIPAYHK';
	public const PAYMENT_TYPE_WECHAT		= 'WECHAT';
	public const PAYMENT_TYPE_GOOGLE		= 'GOOGLE';
	public const PAYMENT_TYPE_APPLE			= 'APPLE';

	public const PAYMENT_TERMINAL_SDK		= 'SDK';
	public const PAYMENT_TERMINAL_WAP		= 'WAP';

	private $_mode			= 'sandbox';
	private $_merchant_id	= null;
	private $_public_key	= null;
	private $_private_key	= null;

	// MODE DEFINITION FUNCTIONS
	public static function getAcceptedModes() {
		return array(
			static::MODE_TESTING => array(
				'id'		=> static::MODE_TESTING, 
				'label'		=> __( 'Testing', 'bbmsl-gateway' ),
				'endpoint'	=> static::API_TESTING_ENDPOINT,
			),
			static::MODE_PRODUCTION => array(
				'id'		=> static::MODE_PRODUCTION, 
				'label'		=> __( 'Production', 'bbmsl-gateway' ),
				'endpoint'	=> static::API_PRODUCTION_ENDPOINT,
			),
		);
	}

	private function getMode() {
		return static::getModeInfo( $this->_mode );
	}

	public static function getModeInfo( string $target_mode = '' ) {
		$modes = static::getAcceptedModes();
		if( isset( $modes[ $target_mode ] ) ) {
			$mode = $modes[ $target_mode ];
			if( isset( $mode ) && is_array( $mode ) && sizeof( $mode ) > 0 ) {
				return $mode;
			}
		}
		return false;
	}

	public static function isModeAccepted( string $mode = '' ) {
		return in_array( $mode, array_keys( static::getAcceptedModes() ), true );
	}

	public function getModeCode() {
		return $this->_mode;
	}

	public function getModeName() {
		$mode = $this->getMode();
		if( $mode && isset( $mode[ 'label' ] ) ) {
			return trim( $mode[ 'label' ] );
		}
	}

	// MUTATOR FUNCTIONS
	public function setGatewayMode( string $mode = '' ) {
		if( isset( $mode ) && is_string( $mode ) ) {
			$mode = strtolower( trim( $mode ) );
			if( ( ! empty( $mode ) ) && static::isModeAccepted( $mode ) ) {
				return $this->_mode = $mode;
			}
		}
		return false;
	}

	public function setMerchantID( $merchant_id = null ) {
		if( isset( $merchant_id ) && Utility::isInt( $merchant_id ) ) {
			$merchant_id = intval( $merchant_id );
			if( $merchant_id > 0 ) {
				$this->_merchant_id = $merchant_id;
			}
		}
		return false;
	}

	public function getMerchantID() {
		return $this->_merchant_id . ''; // convert to string
	}

	public function setPublicKey( ?string $public_key = null ) {
		if( isset( $public_key ) && is_string( $public_key ) ) {
			$public_key = trim( $public_key );
			if( ! empty( $public_key ) ) {
				$this->_public_key = $public_key;
			}
		}
		return false;
	}

	public function setPrivateKey( ?string $private_key = null ) {
		if( isset( $private_key ) && is_string( $private_key ) ) {
			$private_key = trim( $private_key );
			if( ! empty( $private_key ) ) {
				$this->_private_key = $private_key;
			}
		}
		return false;
	}

	private function getPublicKeyPEM() {
		return static::str2pem( $this->_public_key );
	}

	private function getPrivateKeyPEM() {
		return static::str2pem( $this->_private_key, 'RSA PRIVATE' );
	}

	private function getOfficialKeyFile() {
		return BBMSL::getOfficialKeyFile();
	}

	private function getOfficialKey() {
		return static::readCertificateFile( $this->getOfficialKeyFile() );
	}
	
	private static function readCertificateFile( string $path = '' ) {
		$path = realpath( $path );
		if( file_exists( $path ) && !is_dir( $path ) && filesize( $path ) > 0 ) {
			$content = file_get_contents( $path );
			if( static::isPem( $content ) ) {
				return $content;
			}
		}
		return false;
	}

	// ENDPOINT FUNCTIONS
	public function getEndpoint() {
		$mode = $this->getMode();
		if( $mode && isset( $mode[ 'endpoint' ] ) ) {
			return trim( $mode[ 'endpoint' ] );
		}
	}

	public function getPortalLink() {
		$mode = $this->getMode();
		if( isset( $mode ) && isset( $mode[ 'id' ] ) && $mode[ 'id' ] == BBMSL_SDK::MODE_PRODUCTION ) {
			return BBMSL::PRODUCTION_PORTAL_LINK;
		}
		return BBMSL::TESTING_PORTAL_LINK;
	}
	
	// KEY UNTILITY FUNCTIONS
	public static function newKeyPair() {
		$keypair		= RSA::createKey( static::DEFAULT_KEY_SIZE );
		$public_key		= $keypair->getPublicKey();
		$public_str		= $public_key->toString( static::CERT_TYPE_PKCS8 );
		$private_str	= $keypair->toString( static::CERT_TYPE_PKCS1 );
		return array(
			'public'	=> $public_str,
			'private'	=> $private_str,
		);
	}

	public static function pem2str( ?string $pem = null ) {
		if( isset( $pem ) && is_string( $pem ) ) {
			$pem = trim( $pem );
			if( ( ! empty( $pem ) ) && static::isPem( $pem ) ) {
				$pem = explode( "\n", str_replace( "\r", '', $pem ) );
				$pem = array_filter( array_map( 'trim', $pem ) );
				if( is_array( $pem ) && sizeof( $pem ) > 0 ) {
					$pem = implode( array_slice( $pem, 1, -1 ) );
				}
			}
		}
		return $pem;
	}

	public static function str2pem( ?string $key_content = null, string $type = 'PUBLIC' ) {
		if( isset( $key_content ) && is_string( $key_content ) ) {
			$key_content = trim( $key_content );
			if( static::isPem( $key_content ) ) {
				// due to openssl inconsistancies, we'll force reconvert str<>pem to our standard anyways
				$key_content = static::pem2str( $key_content );
			}
			if( ! empty( $key_content ) ) {
				$key_content = preg_replace( '/[^a-z0-9\\/=\+]/i', '', $key_content );
				return sprintf("-----BEGIN %s KEY-----\n%s-----END %s KEY-----\n", $type, chunk_split( $key_content, 64, "\n" ), $type );
			}
		}
		return false;
	}

	public static function isPem( ?string $content = '' ) {
		if( isset( $content ) && is_string( $content ) ) {
			return preg_match( '/-{5}BEGIN (?:PUBLIC|(?:RSA )?PRIVATE) KEY-{5}\r?\n(?:[a-z0-9\\/=\+]{1,64}\r?\n)+\r?\n?-{5}END (?:PUBLIC|(?:RSA )?PRIVATE) KEY-{5}/i', trim( $content ) );
		}
		return false;
	}

	public static function check_key( ?string $key = '' ) {
		if( isset( $key ) && is_string( $key ) ) {
			return preg_match( '/^[a-z0-9\\/=\+]+$/i', trim( $key ) );
		}
		return false;
	}

	public static function createPreVerifyString( array $params = array() ) {
		ksort( $params );
		array_walk( $params, function( &$e, $k ) {
			$e = $k . '=' . $e;
		} );
		return implode( '&', $params );
	}

	public function verify( string $content = '', string $signiture = '' ) {
		if( $public_key_pem = $this->getPublicKeyPEM() ) {	
			if( ! static::isPem( $public_key_pem ) ) {
				$public_key_pem = static::str2pem( $public_key_pem, 'PUBLIC' );
			}
			if( static::isPem( $public_key_pem ) ) {
				return openssl_verify( $content, base64_decode( $signiture ), $public_key_pem, OPENSSL_ALGO_SHA256 );
			}
		}
		return false;
	}

	public function webhookVerify( string $content = '', string $signiture = '' ) {
		if( $public_key_pem = $this->getOfficialKey() ) {
			if( ! static::isPem( $public_key_pem ) ) {
				$public_key_pem = static::str2pem( $public_key_pem, 'PUBLIC' );
			}
			if( static::isPem( $public_key_pem ) ) {
				return openssl_verify( $content, base64_decode( $signiture ), $public_key_pem, OPENSSL_ALGO_SHA256 );
			}
		}
		return false;
	}
	
	public function sign( string $content = '' ) {
		if( $private_key_pem = $this->getPrivateKeyPEM() ) {
			if( ! static::isPem( $private_key_pem ) ) {
				$private_key_pem = static::str2pem( $private_key_pem, 'RSA PRIVATE' );
			}
			if( static::isPem( $private_key_pem ) ) {
				openssl_sign( $content, $encrypted, $private_key_pem, OPENSSL_ALGO_SHA256 );
				return base64_encode( $encrypted );
			}
		}
		return false;
	}
	
	public function signPublic( string $content = '' ) {
		if( $public_key_pem = $this->getPublicKeyPEM() ) {
			if( ! static::isPem( $public_key_pem ) ) {
				$public_key_pem = static::str2pem( $public_key_pem );
			}
			if( static::isPem( $public_key_pem ) ) {
				openssl_sign( $content, $encrypted, $public_key_pem, OPENSSL_ALGO_SHA256 );
				return base64_encode( $encrypted );
			}
		}
		return false;
	}

	private static function getRequestLogFileLocation() {
		return BBMSL_PLUGIN_DIR . 'request.log';
	}

	// ACTUAL API REQUEST FUNCTIONS
	public function makeRequest( string $method = 'GET', string $path = '', array $headers = array(), ?array $params = null ) {
		if( isset( $method ) && is_string( $method ) ) {
			$method = trim( $method );
			if( empty( $method ) ) {
				$method = 'GET';
			}
		}
		if( isset( $path ) && is_string( $path ) ) {
			$path = trim( $path );
			if( ! empty( $path ) ) {
				if( function_exists( 'wp_remote_request' ) &&
					function_exists( 'is_wp_error' ) &&
					function_exists( 'wp_remote_retrieve_response_code' ) &&
					function_exists( 'wp_remote_retrieve_body' ) ) {
					$default_headers = array(
						'accepts'			=> 'application/json',
						'content-type'		=> 'application/json',
						'plugin'			=> 'wordpress',
						'plugin-version'	=> '1.0.8',
					);
					if( isset( $headers ) && is_array( $headers ) ) {
						$headers = array_merge( $headers, $default_headers );
					}
					if( isset( $params ) && is_array( $params ) ) {
						$json = json_encode( $params, JSON_UNESCAPED_UNICODE );
						$sign = $this->sign( $json );
						if( isset( $sign ) && is_string( $sign ) && strlen( $sign ) > 0 ) {
							$payload = array(
								'request'	=> $json,
								'signature'	=> $sign,
							);
							$post = json_encode( $payload, JSON_UNESCAPED_UNICODE );
							$response = wp_remote_request( $this->getEndpoint() . $path, array(
								'method'				=> $method,
								'timeout'				=> 5,
								'redirection'			=> 5,
								'httpversion'			=> '2.0',
								'user-agent'			=> sprintf( 'BBMSL Payment Gateway WordPress Plugin version %s', BBMSL::$version ),
								'reject_unsafe_urls'	=> true,
								'blocking'				=> true,
								'headers'				=> $headers,
								'cookies'				=> array(),
								'body'					=> ( isset( $post ) && is_string( $post ) && strlen( $post ) > 0 ? $post : null ),
								'compress'				=> false,
								'decompress'			=> true,
								'sslverify'				=> true,
								'stream'				=> false,
								'filename'				=> null,
								'limit_response_size'	=> null,
							) );
							if( !is_wp_error( $response ) ) {
								if( 200 == wp_remote_retrieve_response_code( $response ) ) {
									$body = wp_remote_retrieve_body( $response );
									if( Utility::isJson( $body ) ) {
										$json = json_decode( $body, true );
										if( isset( $json ) ) {
											if( isset( $json[ 'result' ] ) ) {
												if( 200 == isset( $json[ 'result' ][ 'code' ] ) ) {
													if( isset( $json[ 'data' ] ) ) {
														return $json[ 'data' ];
													}
													return true;
												}
											}
										}
										return $json;
									}
									return $body;
								}
							}
						}else{
							if($json) {
								throw new \Exception( esc_attr__( 'Failed to sign the request, please contact technical support.', 'bbmsl-gateway' ) );
							}else{
								throw new \Exception( esc_attr__( 'Order production failed, please contact technical support.', 'bbmsl-gateway' ) );
							}
						}
					}
				}
			}
		}
		return false;
	}

	// // Online - Hosted Checkout
	// public function createOrder( Order $order = null, string $merchant_reference = '', bool $is_recurring = false ) {
	// 	if( isset( $order ) && $order instanceof Order &&
	// 		isset( $merchant_reference ) && is_string( $merchant_reference ) ) {
	// 		$merchant_reference = trim( $merchant_reference );
	// 		$is_recurring = boolval( $is_recurring );
	// 		if( ! empty( $merchant_reference ) ) {
	// 			$payload = array(
	// 				'merchantId' => $this->getMerchantID(),
	// 				'amount' => $amount,
	// 				'merchantReference' => $merchant_reference,
	// 				// 'callbackUrl' => array(
	// 				// 	'success' => '',
	// 				// 	'fail' => '',
	// 				// 	'cancel' => '',
	// 				// 	'notify' => '',
	// 				// ],
	// 				'isRecurring' => ( $is_recurring?1:0),
	// 				'lineItems' => array(
	// 					array(
	// 						'quantity' => 5,
	// 						'priceData' => array(
	// 							'unitAmount' => 20,
	// 							'name' => 'Testing Item Name',
	// 						),
	// 					),
	// 				),
	// 			);
	// 			return $this->makeRequest( 'POST', 'hosted-checkout/create/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }

	private function newHostedPayload() {
		return array( 'merchantId' => $this->getMerchantID() );
	}

	private static function coeaseOrderReferences( array &$payload = array(), ?string $order_id = '', string $merchant_reference = '' ) {
		if( isset( $order_id ) ) {
			$order_id = trim( $order_id );
			if( ! empty( $order_id ) ) {
				$payload[ 'orderId' ] = $order_id;
			}
		}
		if( isset( $merchant_reference ) ) {
			$merchant_reference = trim( $merchant_reference );
			if( ! empty( $merchant_reference ) ) {
				$payload[ 'merchantReference' ] = $merchant_reference;
			}
		}
		if( static::hasOrderReference( $payload ) ) {
			return $payload;
		}
		return false;
	}

	private static function hasOrderReference( array &$payload = array() ) {
		if( isset( $payload ) && is_array( $payload ) && sizeof( $payload ) > 0 ) {
			return(
				( isset( $payload[ 'orderId' ] ) && !empty( $payload[ 'orderId' ] ) ) || 
				( isset( $payload[ 'merchantReference' ] ) && !empty( $payload[ 'merchantReference' ] ) )
			);
		}
		return false;
	}
	
	public function voidOrder( string $order_id = '', string $merchant_reference = '' ) {
		if( isset( $order_id ) && is_string( $order_id ) && 
			isset( $merchant_reference ) && is_string( $merchant_reference ) ) {
			$payload = $this->newHostedPayload();
			static::coeaseOrderReferences( $payload, $order_id, $merchant_reference );
			if( static::hasOrderReference( $payload ) ) {
				return $this->makeRequest( 'POST', 'hosted-checkout/void/', array(), $payload );
			}
		}
		return false;
	}
	
	public function queryOrder( string $merchant_reference = '' ) {
		if( isset( $merchant_reference ) && is_string( $merchant_reference ) ) {
			$payload = $this->newHostedPayload();
			static::coeaseOrderReferences( $payload, null, $merchant_reference );
			if( static::hasOrderReference( $payload ) ) {
				return $this->makeRequest( 'POST', 'hosted-checkout/query/', array(), $payload );
			}else{
				throw new \Exception( 'Empty Order Reference in Query Order API call.' );
			}
		}
		return false;
	}
	
	public function refundOrder( string $order_id = '', string $merchant_reference = '', float $amount = 0.0 ) {
		if( 
			isset( $order_id ) && is_string( $order_id ) && 
			isset( $merchant_reference ) && is_string( $merchant_reference ) &&
			isset( $amount ) && is_float( $amount ) > 0
		) {
			$payload = $this->newHostedPayload();
			$payload[ 'amount' ] = $amount;
			static::coeaseOrderReferences( $payload, $order_id, $merchant_reference );
			if( static::hasOrderReference( $payload ) ) {
				return $this->makeRequest( 'POST', 'hosted-checkout/refund/', array(), $payload );
			}
		}
		return false;
	}

	public function recurringOrder( float $amount = 0.0, string $merchant_reference = '', string $parent_order_id = '' ) {
		if(
			isset( $amount ) && is_float( $amount ) && 
			isset( $merchant_reference ) && is_string( $merchant_reference ) && 
			isset( $parent_order_id ) && is_string( $parent_order_id )
		) {
			$payload = $this->newHostedPayload();
			$payload[ 'amount' ] = $amount;
			$payload[ 'merchantReference' ] = trim( $merchant_reference );
			$payload[ 'parentOrderId' ] = trim( $parent_order_id );
			if( $amount > 0 && ( ! empty( $payload[ 'merchantReference' ] ) ) && ( !empty( $payload[ 'parentOrderId' ] ) ) ) {
				return $this->makeRequest( 'POST', 'hosted-checkout/recurring/', array(), $payload );
			}
		}
		return false;
	}

	// // Online tokenization
	// public function addToken( string $user_id = '', string $reason = '' ) {
	// 	if( 
	// 		isset( $user_id ) && is_string( $user_id ) && 
	// 		isset( $reason ) && is_string( $reason )
	// 	) {
	// 		$user_id = trim( $user_id );
	// 		$reason = trim( $reason );
	// 		if( strlen( $user_id ) > 0 && strlen( $reason ) > 0 ) {
	// 			$payload = array(
	// 				'merchantId'	=> $this->getMerchantID(),
	// 				'userId'		=> $user_id, // User ID,must be unique.
	// 				'reason'		=> $reason, // Reason to create token.
	// 				'callbackUrl'	=> array(
	// 					'success'	=> 'xxx',
	// 					'fail'		=> 'xxx',
	// 					'notify'	=> 'xxx',
	// 				),
	// 			);
	// 			return $this->makeRequest( 'POST', 'tokenization/add-token/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }

	// public function queryToken( string $user_id = '' ) {
	// 	if( isset( $user_id ) && is_string( $user_id ) ) {
	// 		$user_id = trim( $user_id );
	// 		if( strlen( $user_id ) > 0 ) {
	// 			$payload = array(
	// 				'merchantId'	=> $this->getMerchantID(),
	// 				'userId'		=> $user_id,
	// 			);
	// 			return $this->makeRequest( 'POST', 'tokenization/query-token/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }

	// public function deleteToken( string $user_id = '', string $token_id = '' ) {
	// 	if( isset( $user_id ) && is_string( $user_id ) && isset( $token_id ) && is_string( $token_id ) ) {
	// 		$user_id = trim( $user_id );
	// 		$token_id = trim( $token_id );
	// 		if( strlen( $user_id ) > 0 && strlen( $token_id ) > 0 ) {
	// 			$payload = array(
	// 				'merchantId'	=> $this->getMerchantID(),
	// 				'userId'		=> $user_id, // Merchant's user ID.
	// 				'tokenId'		=> $token_id, // User's token ID.
	// 			);
	// 			return $this->makeRequest( 'POST', 'tokenization/delete-token/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }

	// public function saleToken( string $user_id = '', string $token_id = '', string $merchant_reference = '', float $amount = 0.0, string $receipt_email = '' ) {
	// 	if( isset( $amount ) && is_numeric( $amount ) && 
	// 		isset( $user_id ) && is_string( $user_id ) && 
	// 		isset( $merchant_reference ) && is_string( $merchant_reference ) && 
	// 		isset( $token_id ) && is_string( $token_id ) ) {
	// 		$amount = doubleval( $amount );
	// 		$user_id = trim( $user_id );
	// 		$token_id = trim( $token_id );
	// 		$merchant_reference = trim( $merchant_reference );
	// 		$receipt_email = trim( $receipt_email );
	// 		if( strlen( $receipt_email ) == 0) {
	// 			$receipt_email = 'info@coding-free.com';
	// 		}
	// 		if( $amount > 0 && strlen( $user_id ) > 0 && strlen( $merchant_reference ) > 0 && strlen( $parent_order_id ) > 0 ) {
	// 			$payload = array(
	// 				'merchantId'		=> $this->getMerchantID(),
	// 				'userId'			=> $user_id, // Merchant's user ID.
	// 				'tokenId'			=> $token_id, // User's token ID.
	// 				'merchantReference'	=> $merchant_reference,
	// 				'amount'			=> $amount,
	// 				'email'				=> $receipt_email,
	// 				'callbackUrl'		=> array(
	// 					'notify'		=> 'xxx',
	// 				),
	// 				'lineItems' => array(
	// 					array(
	// 						'quantity' => 5,
	// 						'priceData' => array(
	// 							'unitAmount' => 20,
	// 							'name' => 'Testing Item Name',
	// 						),
	// 					),
	// 				),
	// 			);
	// 			return $this->makeRequest( 'POST', 'tokenization/sale/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }

	// public function voidToken( string $orderId = '', string $merchant_reference = '' ) {
	// 	if( isset( $orderId ) && is_string( $user_id ) && isset( $merchant_reference ) && is_string( $merchant_reference ) ) {
	// 		$orderId = trim( $orderId );
	// 		$merchant_reference = trim( $merchant_reference );
	// 		if( strlen( $orderId ) > 0 && strlen( $merchant_reference ) > 0 ) {
	// 			$payload = array(
	// 				'merchantId'	=> $this->getMerchantID(),
	// 				'orderId'		=> $orderId,
	// 				'tokenId'		=> $merchant_reference,
	// 			);
	// 			return $this->makeRequest( 'POST', 'tokenization/void/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }

	// public function recurringToken( string $user_id = '', string $token_id = '', string $reason = '', string $merchant_reference = '', string $parent_order_id = '', float $amount = 0.0 ) {
	// 	if( isset( $amount ) && is_numeric( $amount ) && 
	// 		isset( $user_id ) && is_string( $user_id ) && 
	// 		isset( $token_id ) && is_string( $token_id ) && 
	// 		isset( $reason ) && is_string( $reason ) && 
	// 		isset( $merchant_reference ) && is_string( $merchant_reference ) &&
	// 		isset( $parent_order_id ) && is_string( $parent_order_id ) ) {
	// 		$amount = doubleval( $amount );
	// 		$user_id = trim( $user_id );
	// 		$token_id = trim( $token_id );
	// 		$reason = trim( $reason );
	// 		$merchant_reference = trim( $merchant_reference );
	// 		$parent_order_id = trim( $parent_order_id );
	// 		if( $amount > 0 && strlen( $user_id ) > 0 && strlen( $token_id ) > 0 && strlen( $reason ) > 0 && strlen( $merchant_reference ) > 0 && strlen( $parent_order_id ) > 0 ) {
	// 			$payload = array(
	// 				'merchantId'		=> $this->getMerchantID(),
	// 				'userId'			=> $user_id, // Merchant's user ID.
	// 				'tokenId'			=> $token_id, // User's token ID.
	// 				'reason'			=> $reason,
	// 				'amount'			=> $amount,
	// 				'merchantReference'	=> $merchant_reference,
	// 				'parentOrderId'		=> $parent_order_id,
	// 				'callbackUrl' => array(
	// 					'notify' => 'xxx',
	// 				),
	// 			);
	// 			return $this->makeRequest( 'POST', 'tokenization/recurring/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }

	// Online direct
	// public function directSale( string $merchant_reference = '', float $amount = 0.0, string $payment_type = '', string $payment_terminal = '' ) {
	// 	if(
	// 		isset( $amount ) && is_numeric( $amount ) && 
	// 		isset( $merchant_reference ) && is_string( $merchant_reference ) &&
	// 		isset( $payment_type ) && is_string( $payment_type ) &&
	// 		isset( $payment_terminal ) && is_string( $payment_terminal )
	// 	) {
	// 		$amount = doubleval( $amount );
	// 		$merchant_reference = trim( $merchant_reference );
	// 		$payment_type = trim( $payment_type );
	// 		$payment_terminal = trim( $payment_terminal );
	// 		if( $amount > 0 && strlen( $merchant_reference ) > 0 ) {
	// 			$payload = array(
	// 				'merchantId'		=> $this->getMerchantID(),
	// 				'amount'			=> $amount,
	// 				'merchantReference'	=> $merchant_reference,
	// 				'paymentType'		=> $payment_type,
	// 				'paymentTerminal'	=> $payment_terminal,
	// 				'notifyUrl'			=> 'xxx',
	// 				'lineItems' => array(
	// 					array(
	// 						'quantity' => 5,
	// 						'priceData' => array(
	// 							'unitAmount' => 20,
	// 							'name' => 'Testing Item Name',
	// 						),
	// 					),
	// 				),
	// 			);
	// 			return $this->makeRequest( 'POST', 'direct/sale/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }

	// public function voidSale( string $order_id = '', string $merchant_reference = '' ) {
	// 	if(
	// 		isset( $order_id ) && is_string( $order_id ) && 
	// 		isset( $merchant_reference ) && is_string( $merchant_reference )
	// 	) {
	// 		$order_id = trim( $order_id );
	// 		$merchant_reference = trim( $merchant_reference );
	// 		if( strlen( $order_id ) > 0 && strlen( $merchant_reference ) > 0 ) {
	// 			$payload = array(
	// 				'merchantId'		=> $this->getMerchantID(),
	// 				'orderId'			=> $order_id, // 
	// 				'merchantReference'	=> $merchant_reference,
	// 			);
	// 			return $this->makeRequest( 'POST', 'direct/void/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }
	
	// public function querySale( string $order_id = '', string $merchant_reference = '' ) {
	// 	if(
	// 		isset( $order_id ) && is_string( $order_id ) && 
	// 		isset( $merchant_reference ) && is_string( $merchant_reference )
	// 	) {
	// 		$order_id = trim( $order_id );
	// 		$merchant_reference = trim( $merchant_reference );
	// 		if( strlen( $order_id ) > 0 && strlen( $merchant_reference ) > 0 ) {
	// 			$payload = array(
	// 				'merchantId'		=> $this->getMerchantID(),
	// 				'orderId'			=> $order_id, // 
	// 				'merchantReference'	=> $merchant_reference,
	// 			);
	// 			return $this->makeRequest( 'POST', 'direct/query/', array(), $payload );
	// 		}
	// 	}
	// 	return false;
	// }
}
<?php

/**
 * class-webhook.php
 *
 * WordPress Webhook library file
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\Webhook
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.8
 * @since      File available since initial Release.
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

class Webhook
{
	public const WEBHOOK_BBMSL			= 'bbmsl';
	public const ACCEPTED_WEBHOOK_REALM	= 'bbmsl-gateway';

	// Webhook specific utility functions
	private static function getHost() {
		if( function_exists( 'home_url' ) ) {
			$home_url = home_url();
			$parsed = parse_url( $home_url );
			return $parsed[ 'scheme' ] . '://' . $parsed[ 'host' ];
		}
		return false;
	}

	public static function getWebhookUrl() {
		return home_url( '/' . static::ACCEPTED_WEBHOOK_REALM . '/webhook/notification' );
	}

	// WEBHOOK FUNCTIONS
	public static function handleWebhook() {
		add_action( 'init', function() {
			if( isset( $_SERVER[ 'REQUEST_URI' ] ) && is_string( $_SERVER[ 'REQUEST_URI' ] ) ) {
				$host = static::getHost();
				// string
				if( isset( $host ) && is_string( $host ) ) {
					$request_uri = $host . esc_url_raw( trim( $_SERVER[ 'REQUEST_URI' ] ) );
					$home_url = home_url();
					if( 0 === stripos( $request_uri, $host) ) {
						$pathname = substr( $request_uri, strlen( $home_url ) );
						if( stristr( $pathname, '?' ) ) {
							$pathname = substr( $pathname, 0, stripos( $pathname, '?' ) );
						}
						if( stristr( $pathname, '#' ) ) {
							$pathname = substr( $pathname, 0, stripos( $pathname, '#' ) );
						}
						if( strlen( $pathname ) > 0 ) {
							$path_components = array_values( array_filter( explode( '/', trim( $pathname, '/' ) ) ) );
							if( isset( $path_components ) && is_array( $path_components ) && sizeof( $path_components ) > 2) {
								if( $path_components[0] == static::ACCEPTED_WEBHOOK_REALM) {
									$controller 		= $path_components[1];
									$action				= $path_components[2];
									$controller_name	= ucwords( $controller ) . 'Controller';
									$controller_class	= '\\BBMSL\\Controllers\\' . $controller_name;
									$require_file		= BBMSL_PLUGIN_DIR . 'controllers' . DIRECTORY_SEPARATOR . $controller_name . '.php';
									
									add_action( 'init', function() use(
										$require_file,
										$controller,
										$action,
										$controller_name,
										$controller_class
									) {
										$response_message = 'NA';
										$response_code = 0;
										header( 'content-type: text/plain' );
										if( file_exists( $require_file ) ) {
											include( $require_file );
											if( class_exists( $controller_class ) ) {
												if( method_exists( $controller_class, $action) ) {
													list( $response_message, $response_code) = $controller_class::$action();
												}else{
													$response_message = sprintf( esc_attr__( 'Invalid method: %s.', 'bbmsl-gateway' ), $action );
													$response_code = 404;
												}
											}else{
												$response_message = sprintf( esc_attr__( 'Class %s not found in %s.', 'bbmsl-gateway' ), $controller_class, $require_file );
												$response_code = 404;
											}
										}else{
											$response_message = esc_attr__( 'Invalid enpoint', 'bbmsl-gateway' );
											$response_code = 403;
										}
										http_response_code( $response_code );
										print_r( $response_message );
										exit();
									}, BBMSL::PLUGIN_PRIORITY );
								}
							}
						}
					}
				}
			}
			return false;
		}, 0 );
	}
}
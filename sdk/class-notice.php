<?php

/**
 * class-notice.php
 *
 * WordPress notice library file
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\Notice
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
use BBMSL\Sdk\Setup;
use BBMSL\Sdk\WordPress;

class Notice
{
	public const TYPE_SUCCESS	= 'success';
	public const TYPE_WARNING	= 'warning';
	public const TYPE_FAILED	= 'failed';
	public const TYPE_NOTICE	= 'notice';
	public const TYPE_ERROR		= 'error';

	public const DEFAULT_FLASH_CLASS = 'info';

	private static $errors = array(
		Notice::TYPE_ERROR => array(),
		Notice::TYPE_WARNING => array(),
	);

	public static function resetErrors() {
		static::$errors = array(
			Notice::TYPE_ERROR => array(),
			Notice::TYPE_WARNING => array(),
		);
	}

	public static function getNoticeTypes() {
		return array(
			static::TYPE_SUCCESS,
			static::TYPE_WARNING,
			static::TYPE_FAILED,
			static::TYPE_NOTICE,
			static::TYPE_ERROR,
		);
	}

	public static function isAcceptedTypes( string $type = '' ) {
		if( isset( $type ) && is_string( $type ) ) {
			$type = trim( $type );
			return strlen( $type ) > 0 && in_array( $type, static::getNoticeTypes() );
		}
		return false;
	}

	// ADMIN NOTICE FUNCTIONS
	private static function woocommerceInstallLink() {
		return admin_url( 'plugin-install.php?s=WooCommerce&tab=search&type=term' );
	}

	public static function displayNonceExpirationNotice() {
		WordPress::adminNotice( sprintf( '<p>%s</p>', esc_attr__( 'The previous page is expired, please retry.', 'bbmsl-gateway' ) ), static::TYPE_ERROR, true );
	}
	
	public static function displayWooCommerceRequiredNotice() {
		WordPress::adminNotice( sprintf( '<p>%s&nbsp;<a href="%s" class="bbmsl-btn sm">%s</a></p>',
			esc_attr__( 'WooCommerce has not been installed!', 'bbmsl-gateway' ),
			static::woocommerceInstallLink(),
			esc_attr__( 'Install Now', 'bbmsl-gateway' )
		), static::TYPE_ERROR, true );
	}

	public static function displaySetupRequiredNotice() {
		if( !BBMSL::ready() ) {
			WordPress::adminNotice( sprintf( '<p>%s&nbsp;<a href="%s" class="bbmsl-btn sm">%s</a></p>',
				esc_attr__( 'Please complete BBMSL Payment Gateway setup by entering merchant ID and generating new keypair.', 'bbmsl-gateway' ),
				Setup::setupLink(),
				esc_attr__( 'Setup Now', 'bbmsl-gateway' )
			), static::TYPE_WARNING, true );
		}
	}
	
	public static function displayNewKeypairNotice( string $mode = '' ) {
		WordPress::adminNotice( sprintf(
			'<p>%s<br />%s</p>
			 <p><a href="https://merchant.sit.bbmsl.com/account/settings" target="_blank" rel="noreferrer noopener" class="bbmsl-btn sm">%s</a></p>',
			sprintf( esc_attr__( 'New %s key pair generated successfully, please update portal information immediately.', 'bbmsl-gateway' ), $mode),
			esc_attr__( 'Trasactions will not get authenticated if you do not update portal info now.', 'bbmsl-gateway' ),
			esc_attr__( 'Portal Login', 'bbmsl-gateway' )
		), static::TYPE_SUCCESS, true );
	}

	private static function setupNoticeSession() {
		if( !isset( $_SESSION ) ) {
			session_start();
		}
		if( !( isset( $_SESSION[ 'bbmsl_flash' ] ) && is_array( $_SESSION[ 'bbmsl_flash' ] ) ) ) {
			$_SESSION[ 'bbmsl_flash' ] = array();
		}
		return true;
	}
	
	public static function flash( string $content = '', string $class = 'info', bool $dismissible = true, bool $rich_content = false ) {
		if( static::setupNoticeSession() ) {
			$content = trim( $content );
			if( strlen( $content ) > 0 ) {
				if( !$rich_content ) {
					$plaintext = strip_tags( $content );
					if( $content === $plaintext && function_exists( 'wpautop' ) ) {
						$content = wpautop( $content );
					}
				}
				$_SESSION[ 'bbmsl_flash' ][] = array(
					'content'		=> $content,
					'class'			=> $class,
					'dismissable'	=> $dismissible,
					'rich'			=> $rich_content,
				);
			}
			return true;
		}
		return false;
	}

	public static function recall() {
		static::showErrors();
		if( static::setupNoticeSession() ) {
			if( isset( $_SESSION[ 'bbmsl_flash' ] ) && is_array( $_SESSION[ 'bbmsl_flash' ] ) && sizeof( $_SESSION[ 'bbmsl_flash' ] ) > 0 ) {
				foreach( $_SESSION[ 'bbmsl_flash' ] as $k => $message ) {
					if( isset( $message ) && is_array( $message ) && sizeof( $message ) > 0 ) {
						if( isset( $message[ 'content' ] ) && is_string( $message[ 'content' ] ) ) {
							$message_content = trim( $message[ 'content' ] );
						}
						$message_class = static::DEFAULT_FLASH_CLASS;
						if( isset( $message[ 'class' ] ) && is_string( $message[ 'class' ] ) ) {
							$message_class = strtolower( trim( $message[ 'class' ] ) );
						}
						$message_dismissable = true;
						if( isset( $message[ 'dismissable' ] ) ) {
							$message_dismissable = boolval( $message_dismissable );
						}
						if( strlen( $message_content ) > 0 ) {
							WordPress::adminNotice( $message_content, $message_class, $message_dismissable );
						}
						unset( $_SESSION[ 'bbmsl_flash' ][ $k ] );
					}
				}
				return true;
			}
		}
		return false;
	}

	public static function showErrors() {
		if( isset( static::$errors ) && is_array( static::$errors ) && sizeof( static::$errors ) > 0 )  {
			foreach( static::$errors as $flash_class => $notices ) {
				if( isset( $notices ) && is_array( $notices ) )  {
					$notice_count = sizeof( $notices );
					if( $notice_count > 0 ) {
						Notice::flash( 
							wpautop(
								sprintf( esc_attr__( 'We need %d more settings in place to function properly, please correct the following items.', 'bbmsl-gateway' ), sizeof( $notices ) ) . 
								sprintf( '<ul>%s</ul>' , implode( '', array_map( function( $e ) {
									return sprintf( '<li>%s</li>', $e );
								}, $notices ) ) )
							),
							$flash_class,
							true,
							true
						);
						return true;
					}
				}
			}
			return false;
		}
	}

	public static function addError( string $type = '', string $content = '' ) {
		if( static::isAcceptedTypes( $type ) && isset( $content ) && is_string( $content) ) {
			$content = trim( $content );
			if( strlen( $content ) > 0 ) {
				if( !( isset( static::$errors[ $type ] ) && is_array( static::$errors[ $type ] ) ) ) {
					static::$errors[ $type ] = array();
				}
				if( !in_array( $content, static::$errors[ $type ] ) ) {
					static::$errors[ $type ][] = $content;
				}
				return true;
			}
		}
		return false;
	}

	public static function countError(){
		if( isset( static::$errors ) && is_array( static::$errors ) && sizeof( static::$errors ) > 0 )  {
			foreach( static::$errors as $flash_class => $notices ) {
				if( isset( $notices ) && is_array( $notices ) )  {
					return sizeof( $notices );
				}
			}
		}
		return 0;
	}

	public static function hasError() {
		return static::countError() > 0;
	}
}
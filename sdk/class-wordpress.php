<?php

/**
 * class-wordpress.php
 *
 * WordPress compatibility library file
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\WordPress
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
use BBMSL\Sdk\Utility;
use \WP_Screen;

class WordPress
{
	public static function activePlugins() {
		if( function_exists( 'wp_get_active_and_valid_plugins' ) ) {
			$plugins = wp_get_active_and_valid_plugins();
			if( isset( $plugins ) && is_array( $plugins ) && sizeof( $plugins ) > 0 ) {
				return array_map( function( $e ) {
					return basename( dirname( $e ) ) . '/' . basename( $e );
				}, $plugins );
			}
		}
		return [];
	}

	public static function isPluginInstalled( string $plugin = '' ) {
		if( isset( $plugin ) && is_string( $plugin ) ) {
			$plugin = trim( $plugin );
			if( strlen( $plugin ) > 0 ) {
				return in_array( $plugin, static::activePlugins(), true );
			}
		}
		return false;
	}

	public static function adminNotice( string $content = '', string $class = "info", bool $dismissible = true ) {
		echo Utility::safeHTML( sprintf( '<div class="notice notice-%s bbmsl_notice%s">%s</div>', $class, ( $dismissible ? ' is-dismissible' : '' ), $content ), true );
	}

	public static function currentScreen( string $compare = '' ) {
		if( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if( isset( $screen ) && $screen instanceof WP_Screen ) {
				if( ! empty( $compare ) ) {
					return boolval( $compare == $screen->id );
				}
				return $screen->id;
			}
		}
		return false;
	}

	public static function isScreens( array $screens = array() ) {
		if( isset( $screens ) && is_array( $screens ) ) {
			$screens = array_values( array_unique( array_filter( $screens, function($e) {
				if ( is_object($e) && $e instanceof WP_Screen ) {
					if( is_callable( $e, 'id' ) ) {
						$e = $e->id;
					}
				}
				if( is_string( $e ) ) {
					$e = trim( $e );
					if( strlen( $e ) > 0 ) {
						return $e;
					}
				}
				return false;
			} ) ) );
			if( sizeof( $screens ) > 0 ) {
				return in_array( static::currentScreen(), $screens, true );
			}
		}
		return false;
	}

	// OPTION FUNCTIONS
	private static function check_option( string $option = '' ) {
		if( isset( $option ) && is_string( $option ) ) {
			$option = trim( $option );
			if( strlen( $option ) > 0 ) {
				return $option;
			}
		}
		return false;
	}

	public static function default_option( string $option = '' ) {
		if( $option = static::check_option( $option ) ) {
			$defaults = BBMSL::getDefaults();
			if( isset( $defaults ) && is_array( $defaults ) && sizeof( $defaults ) > 0 ) {
				if( isset( $defaults[ $option ] ) ) {
					return $defaults[ $option ];
				}
			}
		}
		return null;
	}
	
	public static function update_option( string $option = '', $value = null, bool $autoload = true ) {
		if( isset( $value ) && function_exists( 'update_option' ) && ( $option = static::check_option( $option ) ) ) {
			return update_option( BBMSL::OPTIONS_PREFIX.$option, $value, $autoload );
		}
		return false;
	}

	public static function get_option( string $option = '', $default = null, bool $strip_slashes = false, bool $skip_default = false ) {
		if( ( $option = static::check_option( $option ) ) && function_exists( 'get_option' ) ) {
			$value = get_option( BBMSL::OPTIONS_PREFIX . $option, $default );
			if( isset( $value ) ) {
				if( is_string( $value ) ) {
					if( $strip_slashes) {$value = stripslashes( $value );}
					return Utility::safeHTML( $value );
				}
				return $value;
			}
		}
		if( isset( $default ) ) {
			return $default;
		}
		if( !$skip_default ) {
			return static::default_option( $option );
		}
		return null;
	}

	public static function has_option( string $option = '' ) {
		return !empty( static::get_option( $option, null, false, true ) );
	}

	public static function plaintext( string $string = '', bool $preserve_new_line = false ) {
		if( $preserve_new_line ) {
			if( function_exists( 'sanitize_textarea_field' ) ) {
				return sanitize_textarea_field( $string );
			}
			return $string;
		}
		if( function_exists( 'sanitize_text_field' ) ) {
			return sanitize_text_field( $string );
		}
		return $string;
	}

	public static function richtext( string $string = '' ) {
		$prev_string = '';
		while( $prev_string == $string ) {
			$prev_string = $string;
			if( function_exists( 'wptexturize' ) ) {
				$string = wptexturize( $string, true );
			}
			if( function_exists( 'wpautop' ) ) {
				$string = wpautop( $string, true );
			}
			$string = trim( $string );
		}
		return $string;
	}
}
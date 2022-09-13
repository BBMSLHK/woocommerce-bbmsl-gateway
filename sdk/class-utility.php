<?php

/**
 * class-utility.php
 *
 * WordPress utility library file
 *
 * @category   Payment Gateway SDK
 * @package    BBMSL\Sdk\Utility
 * @author     chiucs123
 * @copyright  Coding Free Limited since 2022
 * @license    Licensed to BBMSL in 2022
 * @version    1.0.8
 * @since      File available since initial Release.
 * @deprecated -
 */

declare( strict_types = 1 );
namespace BBMSL\Sdk;

use \DateTime;
use \DateTimeZone;

class Utility
{
	public static function isJson( string $string = '' ) {
		json_decode( $string );
		return json_last_error() === JSON_ERROR_NONE;
	}

	public static function isInt( $input ) {
		if( isset( $input ) && in_array( gettype( $input ), array( 'number', 'string' ) ) ) {
			return is_int( $input ) || ctype_digit( $input );
		}
		return false;
	}

	public static function checkBoolean( $input ) {
		if( isset( $input ) && in_array( gettype( $input ), array( 'number', 'string', 'boolean' ) ) ) {
			if( is_string( $input ) ) {$input = strtolower( trim( $input ) );}
			if( is_numeric( $input ) ) {$input = intval( $input );}
			return static::checkBooleanString( $input );
		}
		return false;
	}
	
	public static function checkBooleanString( $input = '' ) {
		return in_array( $input, array( 'yes', 'on', 'true', 'enable', '1', 1, true ), true );
	}

	public static function dateFromTimezone( string $fromTime = '', string $fromTimezone = 'UTC', string $toTimezone = 'Asia/Hong_Kong' ) {
		$from = new DateTimeZone( $fromTimezone );
		$to = new DateTimeZone( $toTimezone );
		$orgTime = new DateTime( $fromTime, $from );
		$toTime = new DateTime( $orgTime->format( "c" ) );
		$toTime->setTimezone( $to );
		return $toTime->format( 'c' );
	}

	public static function safeHTML( string $value = '', bool $allow_links = false ) {
		return strip_tags( $value, '<span><p><i><div><strong><b><img><hr><em><sup><sub><del><h1><h2><h3><h4><h5><h6><pre><s><br><ul><li>' . ( $allow_links ? '<a>' : '' ) );
	}
}
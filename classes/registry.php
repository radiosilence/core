<?php
/**
 * This is like a store for objects.
 *
 * @package core
 * @subpackage core
 */
class registry
{
	private static $vars = array();

	public static function set( $key, $var )
	{
		if( isset( self::$vars[ $key ] ) == true )
		{
			throw new Exception( 'Unable to set var `' . $key . '`. Already set.' );
		}
		self::$vars[ $key ] = $var;
		return true;
	}

	public static function get( $key )
	{
		if( isset( self::$vars[ $key ] ) == false )
		{
			return false;
		}
		return self::$vars[ $key ];
	}

	public static function remove( $var )
	{
		unset( self::$vars[ $key ] );
	}

	public static function spill()
	{
		echo "<pre>";
		print_r( self::$vars );
		echo "</pre>";
	}
}
?>
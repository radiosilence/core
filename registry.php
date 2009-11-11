<?php
/**
 * This is like a store for objects.
 *
 * @package core
 * @subpackage core
 */
class registry
{
	private $vars = array(
	);

	public function set( $key, $var )
	{
		if( isset( $this->vars[ $key ] ) == true )
		{
			throw new Exception( 'Unable to set var `' . $key . '`. Already set.' );
		}
		$this->vars[ $key ] = $var;
		return true;
	}

	public function get( $key )
	{
		if( isset( $this->vars[ $key ] ) == false )
		{
			return false;
		}
		return $this->vars[ $key ];
	}

	public function remove( $var )
	{
		unset( $this->vars[ $key ] );
	}

	public function spill()
	{
		echo "<pre>";
		print_r( $this->vars );
		echo "</pre>";
	}
}
?>
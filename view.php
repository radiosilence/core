<?php
/**
 * Essentially template handling.
 *
 * @package core
 * @subpackage core
 */
class view
{
	private $registry;
	private $vars = array( 
	);

	public function __construct( $registry )
	{
		$this->registry = $registry;
	}

	public function set( $varname, $value, $overwrite = false )
	{
		if( isset( $this->vars[ $varname ] ) == true and $overwrite == false )
		{
			trigger_error( 'Unable to set var `' . $varname . '`. Already set, and overwrite not allowed.', 
					E_USER_NOTICE );
			return false;
		}
		$this->vars[ $varname ] = $value;
		return true;
	}

	public function remove( $varname )
	{
		unset( $this->vars[ $varname ] );
		return true;
	}

	public function show( $name, $bypasslanguages = 0 )
	{
		if(!$bypasslanguages = 0 )
		{
			include SITE_PATH . 'languages' . DIRSEP . LOCALE . '.php';
		}
		$path = SITE_PATH . 'views' . DIRSEP . $name . '.php';

		if( file_exists( $path ) == false )
		{
			trigger_error( 'Template `' . $path . '` does not exist.', E_USER_NOTICE );
			return false;
		}
		// Load variables
		foreach( $this->vars as $key => $value )
		{
			$$key = $value;
		}
		include ( $path );
	}
}
?>
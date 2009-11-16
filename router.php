<?php
/**
 * Gets what we're going to do from the
 * http request.
 *
 * @package core
 * @subpackage core
 */
class router
{
	private $registry;
	private $path;
	private $args = array();

	public function set_path( $path )
	{
		$path .= DIRSEP;
		if( is_dir( $path ) == false )
		{
			throw new Exception( 'Invalid controller path: `' . $path . '`' );
		}
		$this->path = $path;
	}

	public function delegate( $route = 0 )
	{
		# Analyze route
		
		if( !$route )
		{
			$route = ( empty( $_GET[ 'route' ] ) ) ? '' : $_GET[ 'route' ];
		}
		
		$this->get_controller( $file, $controller, $action, $args, $route );
		
		foreach( $args as $k => $v )
		{
			$x = explode( ':', $v );
			if( strlen( $x[ 1 ] ) > 0 )
			{
				$args[ $x[ 0 ] ] = $x[ 1 ];
			}
			unset( $args[ $k ] );
			
		}
	
		$co = $controller;
		$cn = str_replace( "_", DIRSEP, $controller );	
		$file = str_replace( $co, $cn, $file ); 

		# File available?
		if( is_readable( $file ) == false )
		{
			die( "404" );
		}
		# Include the file
		include ( $file );
		# Initiate the class.
		$class = str_replace( "/", "_", 'controller_' . $controller);
		$controller = new $class( $this->registry );

		# If it isn't the action, set the action as index
		if( !is_callable( array( $controller, $action )))
		{
			$action = "index";
		}
		# Run action
		$controller->$action( $args );
	}

	private function get_controller( &$file, &$controller, &$action, &$args, $route = 0 )
	{
		if( empty( $route ) )
		{
			$route = 'index';
		}
		// Get separate parts
		$route = trim( $route, '/\\' );
		$parts = explode( '/', $route );
		// Find right controller
		$cmd_path = $this->path;
		
		foreach( $parts as $part )
		{
			$fullpath = $cmd_path . $part;
			// Is there a dir with this path?
			if( is_dir( $fullpath ) )
			{
				$cmd_path .= $part . DIRSEP;
				array_shift( $parts );
				$class_name[] = $part;
				continue;
			}
			// Find the file
			if( is_file( $fullpath . '.php' ) )
			{
				$controller = $part;
				array_shift( $parts );
				$class_name[] = $part;
				break;
			}
		}
		if( is_array( $class_name ))
		{
			$controller = implode( "_", $class_name );
		}
		if( empty( $controller ) )
		{
			$controller = 'index';
		}
		// Get action
		$action = array_shift( $parts );
		
		array_unshift( $parts, $action );
		
		if( empty( $action ) )
		{
			$action = 'index';
		}
		
		$file = $this->path . $controller . '.php';
		$args = $parts;
	}
}
?>

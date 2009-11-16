<?php
/**
 * Sample index.php for working with core. Should be copied
 * to ../_wwwroot, and this should be set as the web root.
 * Also copy the included .htaccess if using apache.
 * Also recommended:
 * - ../config/database.php (if you want to use database)
 * - ../controllers/index.php
 */

# Definitions
define( "DEBUG", 0 );

define( "DIRSEP", DIRECTORY_SEPARATOR );
define( "LOCALE", "en_GB" );
define( "SITE_PATH", realpath( dirname( __FILE__ ) . DIRSEP . '..' . DIRSEP ) . DIRSEP );
define( "BASE_HREF", preg_replace( "/(.*?)\/index.php/", "$1", $_SERVER[ 'PHP_SELF' ] ) );
define( "CONFIG_PATH", SITE_PATH . DIRSEP . "config" );
define( "CORE_PATH", SITE_PATH . "core" );
define( "HOST", $_SERVER[ "HTTP_HOST" ] );

# If this is set to 1, searching will far faster but less det
# -ailed. (Using mysql full text natural searching). This for
# if there are many articles.
define( "QUICK_SEARCH", 0 );

function __autoload( $class_name )
{
	if( strtolower( substr( $class_name, 0, 5 ) ) == "model"
		 && strtolower( $class_name ) != "model" )
	{
		$class_path = SITE_PATH . "models";
		$class_name = substr( $class_name, 5 );
	}
	else
	{
		$class_path = CORE_PATH;
	}
	$filename = str_replace( "_", DIRSEP, strtolower( $class_name ) ) . '.php';
	
	if( file_exists( $class_path . DIRSEP . $filename ))
	{
		include ( $class_path . DIRSEP . $filename );
	}
	else
	{
		die( "Could not find " . $class_path . DIRSEP . $filename . "!\n" );
		return false;
	}

}

# Load router
$router = new router();
REGISTRY::set( 'router', $router );
$router->set_path( SITE_PATH . 'controllers' );
$router->delegate();
?>
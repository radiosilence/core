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

define( "DIRSEP", DIRECTORY_SEPARATOR );
define( "LOCALE", "en_GB" );
define( "SITE_PATH", realpath( dirname( __FILE__ ) . DIRSEP . '..' . DIRSEP ) . DIRSEP );
define( "BASE_HREF", preg_replace( "/(.*?)\/index.php/", "$1", $_SERVER[ 'PHP_SELF' ] ) );
define( "CONFIG_PATH", SITE_PATH . DIRSEP . "config" );
define( "CORE_PATH", SITE_PATH . DIRSEP . "core" );
define( "HOST", $_SERVER[ "HTTP_HOST" ] );
define( "NONCORE_PATH", SITE_PATH . DIRSEP . "noncore");

# If this is set to 1, searching will far faster but less det
# -ailed. (Using mysql full text natural searching). This for
# if there are many articles.
define( "QUICK_SEARCH", 0 );

function __autoload( $class_name )
{
	$filename = str_replace( "_", DIRSEP, strtolower( $class_name ) ) . '.php';
	
	if( file_exists( CORE_PATH . DIRSEP . $filename ))
	{
		include ( CORE_PATH . DIRSEP . $filename );
	}
	else if( file_exists( NONCORE_PATH . DIRSEP . $filename ) )
	{
		include ( NONCORE_PATH . DIRSEP . $filename );		
	}
	else
	{
		die( "Could not find " . CORE_PATH . DIRSEP . $filename
			. " or " . NONCORE_PATH . DIRSEP . $filename . "!\n" );
		return false;
	}

}

# System happening
$registry = new registry;

# Load router
$router = new router( $registry );
$registry->set( 'router', $router );
$router->set_path( SITE_PATH . 'controllers' );
$router->delegate();
?>
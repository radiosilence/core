<?php
/**
 * Hail Satan.
 *
 * @package core
 * @subpackage core
 * @abstract Extended by the actual controllers
 */
abstract class controller
{
	/**
	 * Gets the database config, returns
	 * a new database.
	 * @return database database object
	 */
	public function database( $args = array() )
	{
		if( !is_array( $args ) )
		{
			die( "Arguments to controller::database must be passed as array." );
		}
		
		extract( $args );
		
		$type 		= $type 	? $type 	: "pdo";
		$name 		= $name 	? $name 	: "db";
		$config_file 	= $config_file 	? $config_file 	: "database";
		
		if( $args[ "name" ] && REGISTRY::get( $name ) )
		{
			return REGISTRY::get( $name );
		}
		else
		{				
			if( !$config_db )
			{
				$conf_path =  CONFIG_PATH . DIRSEP . $config_file . ".php";
		
				if( file_exists( $conf_path ))
				{
					include $conf_path;
				}
				else
				{
					die( "Database requested but no database config available." );
				}
			}
		
			if( $type == "pdo" && class_exists( "PDO" ) )
			{
				$pdo_driver = $args[ "pdo_driver" ] ? $args[ "pdo_driver" ] : "mysql";
				$string = $pdo_driver . ":dbname=" . $config_db[ "database" ] . ";host=" . $config_db[ "hostname" ];
				$database = new PDO( $string, $config_db[ "username" ], $config_db[ "password" ] );
			}
			else
			{	
				$class = "db_" . $type;	
				$database = new $class( $config_db[ "hostname" ],
					$config_db[ "username" ],
					$config_db[ "password" ],
					$config_db[ "database" ]
				);
			}

			REGISTRY::set( $name, $database );				
	
			return $database;
		}
	}

	/**
	 * @return session session object.
	 */
	public function session( $db = false )
	{
		if( $db )
		{
			return new session( $db );
		}
		else
		{
			return new session( REGISTRY::get('db'));
		}
	}
	
	public function load_locale( $l )
	{
		include SITE_PATH . DIRSEP . "languages" . DIRSEP . LOCALE . DIRSEP . $l . ".php";
	}

	/**
	 * All controllers need to have a default option.
	 * @param string $args the arguments got from the URL
	 */
	abstract function index( $args );
}
?>

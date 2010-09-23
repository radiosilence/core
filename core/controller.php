<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


/**
 * Hail Satan.
 *
 * @package core
 * @subpackage core
 * @abstract Extended by the actual controllers
 */

namespace Core;

import('core.registry');
import('core.dependency');

abstract class Controller {

    /**
     * Gets the database config, returns
     * a new database.
     * @return database database object
     */
    public function database($args = array()) {
                
        if(!is_array($args)) {
            throw new Exception("Arguments to controller::database must be passed as array.");
        }
        
        extract($args);
        
        $type = $type ? $type : "pdo";
        $name = $name ? $name : "db";
        $config_file = $config_file ? $config_file : "database";
        
        if($args["name"] && REGISTRY::get($name)) {
            return REGISTRY::get($name);
        } else {
            if(!$config_db) {
                $conf_path = CONFIG_PATH . DIRSEP . $config_file . ".php";
        
                if(!file_exists($conf_path)) {
                    throw new FileNotFoundError($conf_path);
				}	
                require $conf_path;
               
            }
        
            if($type == "pdo") {
                DEPENDENCY::require_classes('PDO');
                $pdo_driver = $args["pdo_driver"] ? $args["pdo_driver"] : "mysql";
                $string = $pdo_driver . ":dbname=" . $config_db["database"] . ";host=" . $config_db["hostname"];
                $database = new \PDO($string, $config_db["username"], $config_db["password"]);
            } else {
                import('core.database.' . strtolower($type));
                $class = "Database\\" . $type;    
                $database = new $class($config_db["hostname"],
                    $config_db["username"],
                    $config_db["password"],
                    $config_db["database"]
               );
            }

            REGISTRY::set($name, $database);                
    
            return $database;
        }
    }

    /**
     * @return session session object.
     */
    public function session($db = false) {
        import('core.session');
        if($db) {
            return new Session($db);
        } else {
            return new Session(REGISTRY::get('db'));
        }
    }
    
    public function load_locale($l) {
        include SITE_PATH . DIRSEP . "languages" . DIRSEP . LOCALE . DIRSEP . $l . ".php";
    }

    /**
     * All controllers need to have a default option.
     * @param string $args the arguments got from the URL
     */
    abstract function index($args);
}
?>

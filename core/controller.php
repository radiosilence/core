<?php /* Copyright 2010 James Cleveland. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY James Cleveland "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL JAMES CLEVELAND OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the
authors and should not be interpreted as representing official policies, either expressed
or implied, of James Cleveland. */


/**
 * Hail Satan.
 *
 * @package core
 * @subpackage core
 * @abstract Extended by the actual controllers
 */

namespace Core;

import('core.dependency');

abstract class Controller {

    /**
     * Gets the database config, returns
     * a new database.
     * @return database database object
     */
    public function database($args = array()) {
                
        if(!is_array($args)) {
            die("Arguments to controller::database must be passed as array.");
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
        
                if(file_exists($conf_path)) {
                    include $conf_path;
                } else {
                    die("Database requested but no database config available.");
                }
            }
        
            if($type == "pdo") {
                DEPENDENCY::require_classes('PDO');
                $pdo_driver = $args["pdo_driver"] ? $args["pdo_driver"] : "mysql";
                $string = $pdo_driver . ":dbname=" . $config_db["database"] . ";host=" . $config_db["hostname"];
                $database = new PDO($string, $config_db["username"], $config_db["password"]);
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

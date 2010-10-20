<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Database;

import('core.dependency');
import('core.exceptions');

class Container {
    private static $pdo;
    
    private $parameters = array();
    public function __construct($parameters=False) {
        if($parameters) {
            $this->parameters = $parameters;        
        }
    }
    
    public static function initiate_static_connection() {
        $container = new Container();
        static::$pdo = $container->get_pdo();
    }
    
    public static function get_default_pdo() {
        if(!(static::$pdo instanceof \PDO)){
            static::initiate_static_connection();
        }
        return static::$pdo;
    }
    
    /**
     * Get a PDO database.
     */
    public function get_pdo() {
        \Core\DEPENDENCY::require_classes('PDO');
        $this->load_config();
        $this->check_config();
        extract($this->parameters['config']);
        if(!isset($driver)) {
            // Defaulting to MySQL for the driver as it is fairly common.
            $driver = 'mysql';
        }
        if($driver == 'mysql') {
            $pdo = new \PDO(sprintf('mysql:host=%s;dbname=%s',
                    $host,
                    $database),
                $user,
                $password
            );
        } else {
            $pdo = new \PDO(sprintf('%s:host=%s;dbname=%s;user=%s;password=%s',
                $driver,
                $host,
                $database,
                $user,
                $password
            ));
        }
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        return $pdo;
    }
    /**
     * Get a living MSSQL object.
     */
    public function get_mssql() {
        import('core.database.mssql');
        $this->load_config();
        $this->check_config();
    }

    /**
     * Load the configuration from a file if it is not set in the parameters.
     */
    private function load_config() {
        if(!isset($this->parameters['config'])) {
            if(!isset($this->parameters['config_file'])) {
                $this->parameters['config_file'] = CONFIG_PATH . "database.php";
            }
            if(!file_exists($this->parameters['config_file'])) {
                throw new \Core\FileNotFoundError($this->parameters['config_file']);
            }
            require($this->parameters['config_file']);
            $this->parameters['config'] = $config_db;
        }
    }

    /**
     * Make sure config is loaded.
     */
    private function check_config() {
        if(!isset($this->parameters['config'])) {
            throw new ConfigNotLoadedError();
        }
    }
}

class ConfigNotLoadedError extends \Core\Error {
    public function __construct() {
        parent::__construct('Config not loaded when trying to initialise database.');
    }
}
?>

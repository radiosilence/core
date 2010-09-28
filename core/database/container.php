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
    private $parameters = array();
    public function __construct($parameters=False) {
        if($parameters) {
            $this->parameters = $parameters;        
        }
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
            return new \PDO(sprintf('mysql:host=%s;dbname=%s',
                    $host,
                    $database),
                $user,
                $password
            );
        } else {
            return new \PDO(sprintf('%s:host=%s;dbname=%s;user=%s;password=%s',
                $driver,
                $host,
                $database,
                $user,
                $password
            ));
        }
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
                $this->parameters['config_file'] = CONFIG_PATH . DIRSEP . "database.php";
            }
            if(!file_exists($this->parameters['config_file'])) {
                throw new \Core\FileNotFoundError($this->parameters['config_file']);
            }
            require_once($this->parameters['config_file']);
            $this->parameters['config'] = $config_db;
        }
    }

    /**
     * Make sure config is loaded.
     */
    private function check_config() {
        if(empty($this->parameters['config'])) {
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
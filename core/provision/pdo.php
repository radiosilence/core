<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Provision;

import('core.types');
import('core.provision');
import('core.dependency');
import('core.containment.pdo');

\Core\DEPENDENCY::require_classes('PDO');

class PDO extends \Core\Provider {
    private static $pdo;
    
    /**
     * Get a PDO database.
     */
    public static function Provide($pdo=False) {
        if($pdo instanceof \PDO) {
            return $pdo;
        }
        if(!(static::$pdo instanceof \PDO)) {
            $provider = new \Core\Containment\PDOContainer();
            static::$pdo = $provider->get_connection();
        }
        
        return static::$pdo;
    }

    public static function set_pdo(\PDO $pdo) {
        static::$pdo = $pdo;
    }

    public function get_connection() {
        $this->load_config('database');
        $this->check_config();
        extract($this->parameters['config_database']);
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
     * Make sure config is loaded.
     */
    private function check_config() {
        if(!isset($this->parameters['config_database'])) {
            throw new ConfigNotLoadedError();
        }
    }
}

class ConfigNotLoadedError extends \Core\Error {
    public function __construct() {
        parent::__construct('Config not loaded when trying to initialise database.');
    }
}

class RequiredPropertyEmptyError extends \Core\Error {
    public function __construct($table) {
        parent::__construct(sprintf("Required property %s is empty.", $table));
    }
}
?>

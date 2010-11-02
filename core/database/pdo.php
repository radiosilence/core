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

import('core.types');
import('core.mapping');
import('core.dependency');

class PDOUtils extends \Core\Arr {
    /**
     * Attach a PDO object. Necessary.
     */
    public function attach_pdo($pdo) {
        $this->pdo = $pdo;
        return $this;
    }

    public static function container($parameters=False) {
        return new PDOContainer($parameters);
    }
}

class PDOContainer extends \Core\ConfiguredContainer {
    private static $pdo;
    
    /**
     * Get a PDO database.
     */
    public function get_pdo() {
        \Core\DEPENDENCY::require_classes('PDO');
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

<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Backend;

import('core.backend');

class PDOContainer extends \Core\BackendContainer {
    protected static $_default_connection = False;
    public function get_backend() {
        if(static::$_default_connection instanceof \PDO) {
            return static::$_default_connection;
        }
        $this->_load_config();
        $this->_check_config();
        extract($this->_config['database']);
        if(!isset($driver)) {
            // Defaulting to MySQL for the driver as it is fairly common.
            $driver = 'mysql';
        }
        $pdo = new \PDO(sprintf('%s:host=%s;dbname=%s',
                $driver,
                $host,
                $database
            ),
            $user,
            $password,
            array(
                \PDO::ATTR_PERSISTENT => true
        ));
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        static::$_default_connection = $pdo;
        return $pdo;
    }
}

?>

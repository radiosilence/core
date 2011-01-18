<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core;

import('core.types');
import('core.exceptions');

abstract class Container {
    protected $parameters = array();
    public function __construct($parameters=False) {
        $this->parameters = $parameters;
    }

    protected function test_valid_parameter($parameter, $type) {
        $is_valid = $this->parameters[$parameter] instanceof $type;
        if(!$is_valid) {
            throw new \Core\Error(sprintf("Trying to use invalid parameter. %s is not an instance of %s.", $parameter, $type));
        }
    }
}
    
abstract class Contained extends \Core\CoreDict {
    public static function container($parameters=False) {
        return static::get_helper('Container', $parameters);
    }
    
    protected static function get_helper($type, $parameters) {
        $class = get_called_class() . $type;
        if(!class_exists($class)) {
            throw new \Core\Error("Class $class does not exist.");
        }
        $helper = new $class($parameters);
        return $helper;
    }
}

abstract class ConfiguredContainer extends Container {

    protected static $loaded_files = array();

    protected $config = array();
    /**
     * Load the configuration from a file if it is not set in the parameters.
     */
    protected function load_config($config_file=Null) {
        if(empty($config_file)) {
            $config_file = SITE_PATH . '/config.php';
        } else {
            $config_file = SITE_PATH . '/config/' . $config_file . '.php';
        }

        if(!file_exists($config_file)) {
            throw new FileNotFoundError($config_file);
        }

        if(!isset(self::$loaded_files[$config_file])) {
            include($config_file);
            self::$loaded_files[$config_file] = $cfg;
        }
        $this->config = self::$loaded_files[$config_file];
    }

}

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
    
abstract class Contained extends Arr {
    public static function container($parameters=False) {
        return static::get_helper('Container', $parameters);
    }
    
    protected static function get_helper($type, $parameters) {
        $class = get_called_class() . $type;
        $helper = new $class($parameters);
        return $helper;
    }
}

abstract class ConfiguredContainer extends Container {
    /**
     * Load the configuration from a file if it is not set in the parameters.
     */
    protected function load_config($config_name=False) {
        if(empty($config_name)) {
            $config_name = strtolower(get_called_class());
        }
        if(!isset($this->parameters['config_' . $config_name])) {
            if(!isset($this->parameters['config_file'])) {
                $this->parameters['config_file'] = CONFIG_PATH . $config_name . ".php";
            }
            if(!file_exists($this->parameters['config_file'])) {
                throw new \Core\FileNotFoundError($this->parameters['config_file']);
            }
            require($this->parameters['config_file']);
            $this->parameters['config_' . $config_name] = $c;
        }
    }

}

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

    public static function create() {
        $cls = get_called_class();
        return new $cls();
    }

    public function __construct($parameters=False) {
        $this->parameters = $parameters;
    }

    protected function test_valid_parameter($parameter, $type) {
        $is_valid = $this->parameters[$parameter] instanceof $type;
        if(!$is_valid) {
            throw new \Core\Error(sprintf("Trying to use invalid parameter. %s is not an instance of %s.", $parameter, $type));
        }
    }

    protected function _get_class() {
        return array_pop(explode("\\", str_replace("Container", null, get_called_class())));
    }
    protected function _get_full_class() {
        return "\\" . str_replace("Container", null, get_called_class());
    }
}

abstract class MappedContainer extends \Core\Container {
    
    public function get_by_field($field, $query) {
        $cls = $this->_get_class();
        $fcls = $this->_get_full_class();
        $objects = $fcls::mapper()
            ->attach_storage(\Core\Storage::container()
                ->get_storage($cls))
            ->get_list(array(
                'filter' => new \Core\Filter($field, $query)
            ));
        return $objects[0];
    }

    public function get_by_id($id) {
        return $this->get_by_field('id', $id);
    }

}
    
abstract class Contained extends \Core\Dict {
    public static function container($parameters=False) {
        return static::get_helper('Container', $parameters);
    }
    
    protected static function get_helper($type, $parameters) {
        $class = get_called_class() . $type;
        if(!class_exists($class)) {
            throw new \Core\Error("Class {$class} does not exist.");
        }
        $helper = new $class($parameters);
        return $helper;
    }

}

abstract class ConfiguredContainer extends Container {

    protected static $_loaded_files = array();

    protected $_config = array();
    /**
     * Load the configuration from a file if it is not set in the parameters.
     */
     
    public function get_container() {
        $this->_load_config();
        $this->_check_config();
        $this->_backend = $this->_config['general']['backend'];
        
        $class = '\\' . str_replace('Container', '\\', get_called_class());
        $container_class = $class . $this->_backend .'Container';
        $container_module = strtolower(str_replace('\\', '.', $class) . $this->_backend); 
        
        import($container_module);

        return $container_class::create();
    }
    protected function _load_config($config_file=Null) {
        if(empty($config_file)) {
            $config_file = SITE_PATH . '/config.php';
        } else {
            $config_file = SITE_PATH . '/config/' . $config_file . '.php';
        }

        if(!file_exists($config_file)) {
            throw new FileNotFoundError($config_file);
        }

        if(!isset(self::$_loaded_files[$config_file])) {
            include($config_file);
            self::$_loaded_files[$config_file] = $cfg;
        }
        $this->_config = self::$_loaded_files[$config_file];
    }
    /**
     * Make sure config is loaded.
     */
    protected function _check_config() {
        if(empty($this->_config)) {
            throw new \Core\Error("Container config not loaded: " . get_called_class());
        }
    }
}

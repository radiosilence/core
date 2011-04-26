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
import('core.storage');
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

    public static function get_class() {
        return array_pop(explode("\\", str_replace("Container", null, get_called_class())));
    }
    public static function get_full_class() {
        return "\\" . str_replace("Container", null, get_called_class());
    }
}

abstract class MappedContainer extends \Core\Container {
    protected static $_hs_index = 0;
    protected $_hs = False;
    protected $_cls;
    protected $_fcls;

    public function __construct($args=False) {
        parent::__construct($args);

        $this->_cls = static::get_class();
        $this->_fcls = static::get_full_class();
    }

    public function get_by_field($field, $query) {
        return $this->get(array(
            'filter' => new \Core\Filter($field, $query)
        ))->{0};
    }

    public function get_by_id($id) {
        $cls = $this->_cls;
        $fcls = $this->_fcls;
        return $this->get_by_field('id', $id);
    }

    public function get($p=False) {
        $cls = $this->_cls;
        $fcls = $this->_fcls;
        $objects = new \Core\Li();

        return $fcls::mapper()
            ->get_list(\Core\Storage::container()
                ->get_storage($cls)
                ->fetch($p)
            );
    }
}
    
abstract class Contained extends \Core\Dict {
    public function __construct($args=False) {
        parent::__construct($args);

        $this->_cls = static::get_class();
        $this->_fcls = static::get_full_class();
    }

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

    public static function get_class() {
        return array_pop(explode("\\", get_called_class()));
    }
    public static function get_full_class() {
        return "\\" . get_called_class();
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
        if(empty($this->_backend)) {
            $this->_backend = 'PDO';
        }
        
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

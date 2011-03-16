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
import('core.containment');

abstract class Mapper extends Dict {
    protected $_storage;
    public function attach_storage(\Core\Storage $storage) {
        $this->_storage = $storage;
        return $this;
    }
    
    public function get_list($items) {
        $objects = new \Core\Li();
        foreach($items as $item) {
            $objects->append($this->create_object($item));            
        }
        return $objects;
    }
    
    public function update($data) {
        $validator = \Core\Validator::validator()
            ->attach_mapper(get_called_class(), $mapper);
    }
    abstract public function create_object($data);
}

abstract class Mapped extends Contained {
    protected $_fields;
    public $_mappers = array();
    abstract public function __construct($init, $sanitize=False) {
        
    }
    
    public static function mapper($parameters=False) {
        return static::get_helper('Mapper', $parameters);
    }

    public function list_fields() {
        return $this->_fields;
    }

    public function attach_mapper($type,$mapper) {
        $this->_mappers[$type] = $mapper;
        return $this;
    }

    public function __toString() {
        return (string)$this->id;
    }
}

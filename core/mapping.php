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
    
    public function get_list(\Core\Dict $parameters) {
        $results = $this->_storage->fetch($parameters);
        $items = new \Core\Li();
        foreach($results as $result) {
            $items->append($this->create_object($result));            
        }
        return $items;
    }
    
    public function update($data) {
        $validator = \Core\Validator::validator()
            ->attach_mapper(get_called_class(), $mapper);
    }
    abstract public function create_object($data);
}

abstract class Mapped extends Contained {
    protected $_fields;
    public $mappers = array();
    public static function mapper($parameters=False) {
        return static::get_helper('Mapper', $parameters);
    }

    public function list_fields() {
        return $this->_fields;
    }

    public function attach_mapper($type,$mapper) {
        $this->mappers[$type] = $mapper;
    }
}

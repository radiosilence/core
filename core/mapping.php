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
    
    public function find_by($field, $pattern){
        $result = $this->_storage->fetch(new \Core\Dict(array(
                "filters" => new \Core\Li(
                    new \Core\Filter($field, $pattern)
                )
        )));
        return $this->create_object($result[0]);
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

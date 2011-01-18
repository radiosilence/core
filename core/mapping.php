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

abstract class Mapper extends CoreDict {
    protected $_storage;
    public function attach_storage(\Core\Storage $storage) {
        $this->_storage = $storage;
        return $this;
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

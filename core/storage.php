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

abstract class Storage extends CoreDict {
    protected $_class;

    public static function create($class) {
        $cls = get_called_class();
        return new $cls($class);
    }

    public function __construct($class) {
        $this->_class = $class;
    }

    protected function _class_name() {
        return $this->_class;
    }
}

class Filter {
    public $field;
    public $pattern;
    public $operand;
    public function __construct($field, $pattern, $operand='='){
        $this->field = $field;
        $this->pattern = $pattern;
        $this->operand = $operand;
    }
    public function hash() {
        return hash("crc32", $field . $pattern . $operand);
    }
}

class Order {
    public $field;
    public $order;
    public function __construct($field, $order='asc') {
        $this->field = $field;
        $this->order = $order;
    }
}
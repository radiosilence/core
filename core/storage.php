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
import('core.exceptions');
abstract class Storage extends \Core\Contained {
    protected $_class;
    protected $_backend;
    
    public static function create($class) {
        $cls = get_called_class();
        return new $cls($class);
    }
    

    public function __construct($class) {
        $this->_class = $class;
    }
   
    public function attach_backend($backend) {
        $this->_backend = $backend;
        return $this;
    }   

    protected function _class_name() {
        return $this->_class;
    }

    abstract public function fetch($parameters=False);
    abstract public function save(\Core\Mapped $object);
    abstract public function delete(\Core\Mapped $object);
    abstract public function get_table_name();
}

class StorageContainer extends \Core\ConfiguredContainer {
    protected $_backend;
    public function get_storage($type) {
        return $this->get_container()
            ->get_storage($type);
    }
}
class StorageError extends \Core\Error {}

class Filter {
    public $field;
    public $pattern;
    public $operand;
    public $hash;
    public function __construct($field, $pattern, $operand='='){
        $this->field = $field;
        $this->pattern = $pattern;
        $this->operand = $operand;
        $this->make_hash();
    }

    private function make_hash() {
        $this->hash = 'p' . hash("crc32", $this->field . $this->pattern . $this->operand);
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

class Join {
    public $foreign;
    public $local;
    public $fields;
    public $subjoins;
    public function __construct($local, $foreign, $fields=False, $subjoins=False) {
        $this->local = $local;
        $this->foreign = $foreign;
        $this->fields = $fields;
        if($subjoins && !is_array($subjoins)) {
            $this->subjoins = array($subjoins);
        } else {
            $this->subjoins = $subjoins;
        }
    }
}

class In {
    public $foreign;
    public $id;
    public function __construct($foreign, $id) {
        $this->foreign = $foreign;
        $this->id = $id;
    }
}
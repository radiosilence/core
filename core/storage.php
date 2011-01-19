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

abstract class Storage extends \Core\Contained {
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

class StorageContainer extends \Core\ConfiguredContainer {
    protected $_backend;
    public function get_storage($type) {
        $this->_load_config();
        $this->_check_config();
        $this->_backend = $this->_config['general']['backend'];

        $storage_class = '\Core\Storage\\'. $this->_backend .'Container';
        $storage_module = 'core.storage.' . strtolower($this->_backend);

        import($storage_module);

        return $storage_class::create()
            ->get_storage($type);
    }

}

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
    public function __construct($local, $foreign, $fields=False) {
        $this->local = $local;
        $this->foreign = $foreign;
        if($fields instanceof \Core\Li) {
            $this->fields = $fields;
        }
    }
}
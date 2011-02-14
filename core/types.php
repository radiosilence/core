<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


/**
 * An attempt to make arrays into objects, I guess.
 */

namespace Core;

import('core.exceptions');

abstract class SuperClass implements \Iterator, \ArrayAccess, \Countable, \Serializable {
    protected $__data__ = array();
    
    public function __construct() {
    }
    
    protected function __name__() {
        return array_pop(explode('\\', get_called_class()));
    }
    
    protected function __fullname__() {
        return '\\' . get_called_class();
    }
    
    public function offsetExists ($offset) {
        return isset($this->__data__[$offset]);
    }
    
    public function offsetGet ($offset) {
        return $this->__data__[$offset];
    }
    
    public function offsetSet ($offset, $value) {
        $this->__data__[$offset] = $value;
    }
    
    public function offsetUnset ($offset) {
        unset($this->__data__[$offset]);
    }

    public function count() {
        return count($this->__data__);
    }

    public function serialize() {
        return serialize($this->__data__);
    }

    public function unserialize($data) {
        $this->__data__ = unserialize($data);
    }
    public function getData() {
        return $this->data;
    }
}   

/**
 * It's a bit like stdClass but better! Woo!
 */
class Li extends SuperClass {
    protected $_position = 0;
    public static function create() {
        $type = get_called_class();
        $li = new $type();
        foreach(func_get_args() as $item){
            $li->extend($item);
        }
        return $li;
    }
    
    public function __construct() {
        foreach(func_get_args() as $item){
            $this->extend($item);
        }
        parent::__construct();
    }

    public function map($function) {
        foreach($this->__data__ as $value) {
            $function($value);
        }
    }
    
    public function append($item) {
        $this->__data__[] = $item;
        return $this;
    }
 
    public function extend($items) {
        if($items instanceof \Core\Li) {
            $items = $items->__array__();
        }
        if(!is_array($items)) {
            $items = array($items);
        }
        foreach($items as $item) {
            $this->append($item);
        }
        return $this;
    }

    public function insert($position,$item) {
        $tail = array_splice($this->__data__, $position);
        $this->__data__[] = $item;
        $this->__data__ = array_merge($this->__data__, $tail);
        return $this;
    }

    public function __array__() {
        return $this->__data__;
    }

    
    public function rewind() {
        $this->_position = 0;
    }

    public function current() {
        return $this->__data__[$this->_position];
    }

    public function key() {
        return $this->_position;
    }

    public function next() {
        ++$this->_position;
    }

    public function valid() {
        return isset($this->__data__[$this->_position]);
    }
}

class Dict extends SuperClass {
    public function __construct($init=False) {
        if(is_array($init) or $init instanceof Dict) {        
            foreach($init as $k => $v) {
                $this->__data__[$k] = $v;
            }
        }
        parent::__construct();
    }
    public static function create($init=False) {
        $type = get_called_class();
        $dict = new $type($init);
        return $dict;
    }

    public function remove($key) {
        unset($this->__data__[$key]);
    }

    public function map($function) {
        foreach($this->__data__ as $key => $value) {
            $function($key, $value);
        }
    }

    public function overwrite($array) {
        foreach($array as $key => $value) {
            if($parameters['exclude']) {
                if(!in_array($key, $parameters['exclude'])) { 
                    $this->__data__[$key] = $value;
                }
            } else {
                $this->__data__[$key] = $value;
            }
        }
    }
       
    public function __get($key) {
        if(array_key_exists($key, $this->__data__)) {
            return $this->__data__[$key];   
        } else {
            return False;
        }
    }

    public function __set($key, $value) {
        $this->__data__[$key] = $value;
    }

    public function __array__() {
        return $this->__data__;
    }
    
    public function rewind() {
        return \reset($this->__data__);
    }

    public function current() {
        return \current($this->__data__);
    }

    public function key() {
        return \key($this->__data__);
    }

    public function next() {
        return \next($this->__data__);
    }

    public function valid() {
        return \key($this->__data__) !== null;
    }

}

class ListIsNotADictError extends \Core\Error {
    public function __construct() {
        parent::__construct("List is not a dictionary.");
    }
}

class Range extends Li {
    public static function create() {
        $type = get_called_class();
        $arr = new $type();
        $args = func_get_args();
        if(func_num_args() == 1) {
            $range = range($args[0]);
        } else {
            $range = range($args[0],$args[1]);
        }
        $arr->extend($range);
        return $arr;
    }
}
?>

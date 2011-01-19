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

/**
 * It's a bit like stdClass but better! Woo!
 */
class Li {
    protected $__data__ = array();
    protected $parameters;

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
    }

    public function __get($key) {
        if(array_key_exists($key, $this->__data__)) {
            return $this->__data__[$key];   
        } else {
            return False;
        }
    }
    
    public function __set($key, $value) {
        if(!is_numeric($key)) {
            throw new ListIsNotADictError();
        }
        $this->__data__[$key] = $value;
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

    /**
     * Counts the values
     * @return int
     */
    public function count($value=False) {
        if($value) {
            $counts = array_count_values($this->__data__);
            return $counts[$value];    
        } else {
            return count($this->__data__);
        }
    }

    public function __array__() {
        return $this->__data__;
    }
}

class Dict {
    protected $__data__ = array();
    protected $parameters;

    public function __construct($array=False) {
        if(is_array($array)) {
            foreach($array as $k => $v) {
                $this->__data__[$k] = $v;
            }
        }
    }
    public static function create($array=False) {
        $type = get_called_class();
        $dict = new $type($array);
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

    public function update($array) {
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
}

class ListIsNotADictError extends Error {}

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

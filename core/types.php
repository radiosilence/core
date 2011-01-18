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
class CoreList {
    protected $_data = array();
    protected $parameters;

    public static function create() {
        $type = get_called_class();
        $arr = new $type();
        foreach(func_get_args() as $item){
            $arr->extend($item);
        }
        return $arr;
    }

    public function __get($key) {
        if(array_key_exists($key, $this->_data)) {
            return $this->_data[$key];   
        } else {
            return False;
        }
    }
    
    public function __set($key, $value) {
        if(!is_numeric($key)) {
            throw new ListIsNotADictError();
        }
        $this->_data[$key] = $value;
    }

    public function map($function) {
        foreach($this->_data as $value) {
            $function($value);
        }
    }
    
    public function append($item) {
        $this->_data[] = $item;
        return $this;
    }
 
    public function extend($items) {
        if($items instanceof \Core\Arr) {
            $items = $items->_array();
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
        $tail = array_splice($this->_data, $position);
        $this->_data[] = $item;
        $this->_data = array_merge($this->_data, $tail);
        return $this;
    }

    /**
     * Counts the values
     * @return int
     */
    public function count($value=False) {
        if($value) {
            $counts = array_count_values($this->_data);
            return $counts[$value];    
        } else {
            return count($this->_data);
        }
    }

    public function _array() {
        return $this->_data;
    }
}

class CoreDict {
    protected $_data = array();
    protected $parameters;

    public static function create($array=False) {
        $type = get_called_class();
        $arr = new $type();
        if(is_array($array)) {
            foreach($array as $k => $v) {
                $arr[$k] = $v;
            }
        }
        return $arr;
    }

    public function remove($key) {
        unset($this->_data[$key]);
    }


    public function update($array) {
        foreach($array as $key => $value) {
            if($parameters['exclude']) {
                if(!in_array($key, $parameters['exclude'])) { 
                    $this->_data[$key] = $value;
                }
            } else {
                $this->_data[$key] = $value;
            }
        }
    }
       
    public function __get($key) {
        if(array_key_exists($key, $this->_data)) {
            return $this->_data[$key];   
        } else {
            return False;
        }
    }
    
    public function __set($key, $value) {
        $this->_data[$key] = $value;
    }

    public function _array() {
        return $this->_data;
    }
}

class ListIsNotADictError extends Error {}

class Range extends CoreList {
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

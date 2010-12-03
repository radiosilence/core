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
class Arr {
    protected $data = array();
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
        if(array_key_exists($key, $this->data)) {
            return $this->data[$key];   
        } else {
            return False;
        }
    }
    
    public function __set($key, $value) {
        $this->data[$key] = $value;
    }

    public function set_key($key, $subkey, $value) {
        $this->data[$key][$subkey] = $value;
    }

    public function map($function) {
        foreach($this->data as $value) {
            $function($value);
        }
    }
    
    public function append($item) {
        $this->data[] = $item;
        return $this;
    }

    public function update($array) {
        foreach($array as $key => $value) {
            if($parameters['exclude']) {
                if(!in_array($key, $parameters['exclude'])) { 
                    $this->data[$key] = $value;
                }
            } else {
                $this->data[$key] = $value;
            }
        }
    }
        
    public function extend($items) {
        if($items instanceof \Core\Arr) {
            $items = $items->_array();
        }
        if(!is_array($items)) {
            $items = array($items);
        }
        $this->data = array_merge($this->data, $items);
        return $this;
    }

    public function insert($position,$item) {
        $tail = array_splice($this->data, $position);
        $this->data[] = $item;
        $this->data = array_merge($this->data, $tail);
        return $this;
    }

    public function remove($key) {
        unset($this->data[$key]);
    }

    /**
     * Counts the values
     * @return int
     */
    public function count($value=False) {
        if($value) {
            $counts = array_count_values($this->data);
            return $counts[$value];    
        } else {
            return count($this->data);
        }
    }

    public function _array() {
        return $this->data;
    }
}

class Range extends Arr {
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

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
    private $data = array();

    public function __construct() {
        foreach( func_get_args() as $item ){
            $this->append( $item );
        }
    }
    
    public function __get($key) {
        return $this->data[$key];
    }
    
    public function __set($key, $value) {
        $this->data[$key] = $value;
    }
    
    public function append($item) {
        $this->data[] = $item;
        return $this;
    }
    
    public function extend($items) {
        if($items instanceof \Core\Arr) {
            $items = $items->_to_array();
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

    /**
     * Counts the values
     * @return int
     */
    public function count($value) {
        $counts = array_count_values($this->data);
        return $counts[$value];
    }

    public function _to_array() {
        return (array)$this->data;
    }
}
?>

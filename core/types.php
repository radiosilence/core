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

class Arr {
    private $elements = array();

    public function __construct() {
        foreach( func_get_args() as $item ){
            $this->append( $item );
        }
    }

    public function append($item) {
        $this->elements[] = $item;
    }

    public function extend($items) {
        if(!is_array($items)) {
            $items = array($items);
        }
        foreach($items as $item){
            $this->elements[] = $item;
        }
    }

    public function insert($position,$item) {
        $tail = array_splice($this->elements, $position);
        $this->elements[] = $item;
        $this->elements = array_merge($this->elements, $tail);
    }

    /**
     * Counts the values
     * @return int
     */
    public function count($value) {
        $counts = array_count_values($this->elements);
        return $counts[$value];
    }

    public function __toArray() {
        return $this->elements;
    }
}
?>
<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Superclass;

import('core.types');

abstract class Data {
    /**
     * Data storage.
     */
    protected $data;

    public function __construct() {
        $this->data = new \Core\Arr;
    }
    public function __set($key, $value) {
        $this->data->$key = $value;
    }   
    public function __get($key) {
        return $this->data->$key;
    }
}
?>

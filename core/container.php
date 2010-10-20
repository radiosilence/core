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

abstract class Container {
    protected $parameters = array();
    public function __construct($parameters) {
        $this->parameters = $parameters;
    }

    protected function test_valid_parameter($parameter, $type) {
        $is_valid = $this->parameters[$parameter] instanceof $type;
        if(!$is_valid) {
            throw new \Core\Error(sprintf("Trying to use invalid parameter. %s is not an instance of %s.", $parameter, $type));
        }
    }
}
?>
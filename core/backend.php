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

import('core.containment');

abstract class Backend extends \Core\Contained {}
class BackendContainer extends \Core\ConfiguredContainer {
    protected $_backend;
    public function get_backend() {
        return $this->get_container()
            ->get_backend();
    }
}

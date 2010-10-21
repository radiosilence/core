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

import('core.types');
import('core.containment');

abstract class Mapper extends Arr {
    abstract public function create_object($data);
}

abstract class Mapped extends Contained {
    public static function mapper($parameters=False) {
        return static::get_helper('Mapper', $parameters);
    }
}

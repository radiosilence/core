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

import('core.superclass.pdo');
import('core.superclass.standard');

abstract class Mapper extends \Core\Superclass\PDODependent {
    private $_select;
    private $_joins;
    abstract public function create_object($data);
}

abstract class Mapped extends \Core\Superclass\Data {
    public function __construct($data=False) {
        parent::__construct();
        if($data) {
            $this->data = $data;
        }
    }
    public static function mapper() {
        $class = get_called_class() . 'Mapper';
        $mapper = new $class();
        $mapper->attach_pdo();
        return $mapper;
    }
    
}

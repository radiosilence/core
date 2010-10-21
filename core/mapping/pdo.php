<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Mapping;

import('core.mapping');
import('core.database.pdo');

abstract class PDOMapped extends \Core\Mapped {
    public static function mapper() {
        $mapper = parent::mapper()
            ->attach_pdo();
        return $mapper;
    }
}

abstract class PDOMapper extends \Core\Mapper {
    protected $_select;
    protected $_joins;
    protected $pdo;
    
    public function attach_pdo($pdo=False) {
        $this->pdo = \Core\Database\PDOUtils::attach_pdo($pdo);
        return $this;
    }    
}

?>

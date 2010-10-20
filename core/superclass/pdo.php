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
import('core.superclass.standard');

abstract class PDODependent extends \Core\Superclass\Data {
    /**
     * PDO instance
     */
    protected $pdo;
    
    /**
     * Attach a PDO object. Necessary.
     */
    public function attach_pdo($pdo=False) {
        if(!$pdo) {
            import('core.database.container');
            $pdo = \Core\Database\CONTAINER::get_default_pdo();
        }
        $this->pdo = $pdo;
        return $this;
    }
}

class RequiredPropertyEmptyError extends \Core\Error {
    public function __construct($table) {
        parent::__construct(sprintf("Required property %s is empty.", $table));
    }
}
?>

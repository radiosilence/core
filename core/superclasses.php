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

abstract class PDODependentClass {
    /**
     * PDO instance
     */
    protected $pdo;
    
    /**
     * Attach a PDO object. Necessary.
     */
    public function attach_pdo(\PDO $pdo) {
        $this->pdo = $pdo;
        return $this;
    }
}
?>
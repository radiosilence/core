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
    public function attach_pdo(\PDO $pdo) {
        $this->pdo = $pdo;
        return $this;
    }
}

abstract class PDOStored extends PDODependent {

    /**
     * Store for objects already read from database.
     */
    protected static $cache;
    protected static $table;
     
    public function __construct() {
        parent::__construct();
        static::$cache = new \Core\Arr;
        if(empty(static::$table)){
            throw new RequiredPropertyEmptyError('table');
        }
    }
   
    public function populate_cache($ids) {
    
    }
    
    public function load($id) {
        $this->id = $id;
        try {
            $this->load_from_cache();
        } catch(NotFoundInCacheError $e) {
            $this->load_from_pdo();
            $this->store_in_cache($this->id, $this->data);
        }
    }
    
    private function load_from_cache() {
        if(!isset(static::$cache->$this->id)) {
            throw new NotFoundInCacheError();
        }
        $this->data = static::$cache->$this->id;
    }
    
    abstract protected function load_from_pdo();
    
    private function store_in_cache($id, $data) {
        static::$cache->$id = $data;
    }

}

class NotFoundInCacheError extends \Core\Error {}
class RequiredPropertyEmptyError extends \Core\Error {
    public function __construct($table) {
        parent::__construct(sprintf("Required property %s is empty.", $table));
    }
}
?>

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

abstract class PDOStored {

    /**
     * Store for objects already read from database.
     */
    protected static $cache;
    protected static $table;
    protected static $pdo;
    
    protected $id;
    protected $is_loaded = False;
         
    public function __construct() {
        static::$cache = new \Core\Arr;
        if(empty(static::$table)){
            throw new RequiredPropertyEmptyError('table');
        }
    }
   
    public static function attach_pdo(\PDO $pdo) {
        static::$pdo = $pdo;
    }
   
    public function populate_cache($ids=False) {
        $sth = static::$pdo->prepare("
            SELECT *
            FROM :table" .
            function($ids) {
                
            }
        );
    }
    
    public function load($id) {
        $this->id = $id;
        try {
            $this->load_from_cache();
        } catch(NotFoundInCacheError $e) {
            $this->load_from_pdo();
            $this->store_in_cache($this->id, $this->data);
        }
        $this->is_loaded = True;
    }
    
    public function is_loaded() {
        return $this->is_loaded;
    }
    
    private function load_from_cache() {
        $id = $this->id;
        if(!isset(static::$cache->$id)) {
            throw new NotFoundInCacheError();
        }
        $this->data = static::$cache->$id;
    }
    
    protected function load_from_pdo() {
        $sth = static::$pdo->prepare( "
            SELECT *
            FROM " . static::$table . "
            WHERE id = :id
        ");

        $sth->execute(array(
            ':id' => $this->id
        ));
        $this->data = $sth->fetchObject();
    }
    
    private function store_in_cache($id, $data) {
        static::$cache->$id = $data;
    }

}

class NotFoundInCacheError extends \Core\Error {
    public function __construct() {
        parent::__construct("Object not found in shared cache.");
    }
}
class RequiredPropertyEmptyError extends \Core\Error {
    public function __construct($table) {
        parent::__construct(sprintf("Required property %s is empty.", $table));
    }
}
?>

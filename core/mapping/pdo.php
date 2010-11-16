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

/**
 * Provides some basic mapping features that don't necessarily have to be used.
 */
abstract class PDOMapper extends \Core\Mapper {
    protected $pdo;
    protected
        $_select,
        $_delete,
        $_insert,
        $_update,
        $_joins,
        $_fields;

    protected
        $_default_select = "SELECT * FROM %s",
        $_default_delete = "DELETE * FROM %s",
        $_default_update = "UPDATE %s SET",
        $_default_insert = "INSERT INTO %s";

    public function __construct($parameters=False){
        //$this->pdo = \Core\Provision\PDO::Provide($parameters['pdo']);
    }

    public function attach_pdo(\PDO $pdo) {
        $this->pdo = $pdo;
        return $this;
    }
    public function fetch_all() {
        $objects = array();
        $sth = $this->pdo->prepare(sprintf(
            "%s\n%s\n%s",
            $this->_head('select'), 
            $this->_joins,
            $this->_order
        ));
        $sth->execute();
        $results = $sth->fetchAll(PDO::FETCH_ASSOC);

        foreach($results as $result) {
            $objects[] = $this->create_object($result);
        }
        return $objects;
    }

    public function fetch($id) {
        $sth = $this->pdo->prepare(sprintf(
            "%s\n%s\nWHERE id = :id",
            $this->_head('select'), 
            $this->_joins
        ));
        $sth->execute(array(
            ':id' => $id
        ));
        $result = $sth->fetch(PDO::FETCH_ASSOC);
        return $this->create_object($result);
    }

    public function save(Mapped $object) {
        if($object->id > 0) {
            $this->_update($object);
        } else {
            $this->_insert($object);   
        }
    }

    protected function _insert(Mapped $object) {
        $data = $this->_filter($object->_array());
        $sth = $this->pdo->prepare(sprintf(
            "%s\n(%s)\nVALUES (%s)
	    RETURNING id",
            $this->_head('insert'),
            $this->_insert_fields($data),
            $this->_insert_fields($data,':')
        ));
        $sth->execute($this->_binds($data));
	$inserted = $sth->fetch();
	return $inserted['id'];
    }

    protected function _insert_fields($data, $prefix=False) {
        if(!prefix){
            $sqls = array('id');   
        } else {
            $sqls = array('NULL');
        }
        $sqls = array();
        foreach($data as $key => $value) {
            $sqls[] = $prefix . $key;
        }
        return implode(",", $sqls);
    }

    protected function _update(Mapped $object) {
        $data = $this->_filter($object->_array());
        $sth = $this->pdo->prepare(sprintf(
            "%s\n%s\nWHERE id = :id",
            $this->_head('update'),
            $this->_update_fields($data)
        ));
        $sth->execute($this->_binds(
                $data,
                $object->id
        ));
    }

    protected function _update_fields($data) {
        $sqls = array();
        foreach($data as $key => $value) {
            $sqls[] = sprintf('`%1$s` = :%1$s', $key);
        }
        return implode(",\n", $sqls);
    }

    protected function _binds($data, $id=False) {
        if($id) {
            $binds = array(':id' => $id);        
        } else {
            $binds = array();
        }
        foreach($data as $key => $value) {
            $binds[':' . $key] = $value;
        }
        return $binds;
    }

    protected function _filter($data) {
        $returns = array();
        foreach($data as $key => $value) {
            if(in_array($key, $this->_fields)) {
                $returns[$key] = $value;
            }            
        }
        return $returns;
    }

    public function delete($id) {
        $sth = $this->pdo->prepare(
            $this->_head('delete') . 
            " WHERE id = :id");
        $sth->execute(array(
            ':id' => $id
        ));
    }

    protected function _head($type) {
        if(strlen($this->$type) > 0) {
            return $this->$type;
        } else {
            $var_name = '_default_' . $type;
            return sprintf($this->$var_name, $this->_default_table());
        }
    }

    protected function _default_table() {
        $class = explode("\\", $this->mapped_class_name());
        array_shift($class);
        return strtolower(implode("\\", $class));
    }
}


?>

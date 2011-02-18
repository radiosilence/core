<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Storage;

import('core.storage');


class PDOContainer extends \Core\ConfiguredContainer {
    public function get_storage($type) {
        return PDO::create($type)
            ->attach_backend(
                \Core\Backend::container()
                    ->get_backend()
            );
    }
}

/**
 * Provides some basic mapping features that don't necessarily have to be used.
 */
class PDO extends \Core\Storage {

    public function attach_backend($backend) {
        $this->_backend = $backend;
        return $this;
    }

    /**
     * TODO: Make this work based on the parameters.
     */
    public function fetch(\Core\Dict $parameters=Null) {
        $objects = array();
        
        $query = new PDOQuery(
            PDOQuery::Select,
            $this->_default_table(),
            $parameters
        );
        $sth = $this->_backend->prepare($query->sql());
        if($parameters->filters) {
            foreach($parameters->filters as $filter) {
                if(is_int($filter->pattern)) {
                    $type = \PDO::PARAM_INT;
                } else {
                    $type = \PDO::PARAM_STR;
                }
                $sth->bindValue(':' . $filter->hash, $filter->pattern, $type);
            }            
        }
        $sth->execute();
        $items = new \Core\Li();
        foreach($sth->fetchAll(\PDO::FETCH_ASSOC) as $item) {
            $items->append($item);
        }
        return $items;
    }

    public function save(\Core\Mapped $object) {
        if($object->id > 0) {
            $this->_update($object);
        } else {
            $this->_insert($object);   
        }
    }

    protected function _insert(\Core\Mapped $object) {
        $data = $this->_filter($object->_array());
        $sth = $this->_backend->prepare(sprintf(
            "%s\n(%s)\nVALUES (%s)
        RETURNING id",
            $this->_head('insert'),
            $this->_insert_fields($data),
            $this->_insert_fields($data, ':')
        ));
        $sth->execute($this->_binds($data));
       $inserted = $sth->fetch();
       return $inserted['id'];
    }
    
    protected function _update(\Core\Mapped $object) {
        $data = $this->_filter($object->_array(), $object->list_fields());
        $sth = $this->_backend->prepare(sprintf(
            "%s\n%s\nWHERE id = :id",
            $this->_head('update'),
            $this->_update_fields($data)
        ));
        $sth->execute($this->_binds(
                $data,
                $object->id
        ));
    }

    protected function _filter($data, $fields) {
        $returns = array();
        foreach($data as $key => $value) {
            if(in_array($key, $fields)) {
                $returns[$key] = $value;
            }            
        }
        return $returns;
    }

    public function delete(\Core\Dict $parameters=Null) {
/*        $sth = $this->_backend->prepare(
            $this->_head('delete') . 
            " WHERE id = :id");
        $sth->execute(array(
            ':id' => $id
        ));
*/
    }
    
    public function get_table_name() {
        return $this->_default_table();
    }
    
    /**
     * Create a table_name based on ClassName
     * TODO: Proper grammatical plurals, ala Django or SQLAlchemy
     */
    protected function _default_table() {
        return strtolower($this->_class_name()) . 's';
    }

    protected function _filter_to_bind(\Core\Storage\Filter $filter) {
        return array(":" . $filter->hash(), $filter->pattern );
    }
}

class PDOQuery {

	const Select = 'SELECT %1$s FROM %2$s';
    const Delete = 'DELETE * FROM %s';
    const Update = 'UPDATE %s SET';
    const Insert = 'INSERT INTO %s';
    
    protected $_parameters;
    protected $_table;
    protected $_type;
    
    private $_filter_no = 0;
    public function __construct($type, $table, $parameters) {
        $this->_parameters = $parameters;
        $this->_table = $table;
        $this->_type = $type;
    }

    public function sql() {
        return sprintf("%s %s %s %s",
            $this->_head(),
            $this->_joins(),
            $this->_filters(),
            $this->_orders()
        );
    }

    protected function _head() {
        if($this->_type == PDOQuery::Select) {
            $fields = new \Core\Li($this->_table . '.*');
            if($this->_parameters->joins){
                $fields->extend($this->_join_fields());                
            }
            return sprintf($this->_type,
                implode(', ', $fields->__array__()),
                $this->_table
            );
        } else {
            return sprintf($this->_type, $this->_table);            
        }
    }

    protected function _get_lines($type) {
        $string = "";
        $types = "{$type}s";
        if($this->_parameters->$type) {
            $this->_parameters->$types = new \Core\Li(
                $this->_parameters->$type
            );
        }
        if(!$this->_parameters->$types) {
            return False;
        }
        foreach ($this->_parameters->$types->__array__() as $item) {
            $f = "_{$type}_to_sql";
            $string .= ' ' . $this->$f($item);
        }
        return $string;
    }
    
    protected function _joins() {
        return $this->_get_lines('join');
    }
    
    private function _join_to_sql(\Core\Join $join) {
        return sprintf('LEFT JOIN %1$s %3$s on %2$s.%3$s = %3$s.id',
            \Core\Storage\PDO::create($join->foreign)
                ->get_table_name(),
            $this->_table,
            $join->local
       );
    }

    private function _join_fields() {
        $fields = new \Core\Li();
        $this->_parameters->joins->map(function($join) use($fields) {
            if($join->fields) {
                $join->fields->map(function($field) use($fields, $join) {
                    $fields->extend(sprintf('%1$s.%2$s as %1$s_%2$s',
                    $join->local,
                    $field));
                });
            }
        });
        return $fields;
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
    
    protected function _update_fields($data) {
        $sqls = array();
        foreach($data as $key => $value) {
            $sqls[] = sprintf('%1$s = :%1$s', $key);
        }
        return implode(",\n", $sqls);
    }
    
    protected function _filters() {
        $i = 0;
        $string = "";
        if(!$this->_parameters->filters) {
            return False;
        }
        foreach ($this->_parameters->filters->__array__() as $item) {
            $string .= ($i > 0 ? ' AND ' : 'WHERE ') . $this->_filter_to_sql($item);
            $i++;
        }
        return $string;
    }
    
    public function _filter_to_sql(\Core\Filter $filter) {
        $return = sprintf("%s %s %s",
            $this->_table . '.' . $filter->field,
            $filter->operand,
            ':' . $filter->hash
        );
        return $return;
    }
    
    protected function _orders() {
        return $this->_get_lines('order');    
    }
    public function _order_to_sql(\Core\Order $order) {
        return sprintf("ORDER BY %s %s",
            $order->field, $order->order
        );
    }
}


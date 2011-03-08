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
import('core.types');
import('core.utils.language');

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

    protected function _expand_singular_parameters($pars) {
        foreach(array('join', 'filter', 'in', 'order', 'field') as $t) {
            if(isset($pars[$t])) {
                $pars[\Core\Utils\Language::plural($t)][] = $pars[$t];
                unset($pars[$t]);
            }
        }
        return $pars;
    }
    
    public function fetch($parameters=False) {
        $objects = array();
        $parameters = $this->_expand_singular_parameters($parameters);
        if(is_array($parameters['in'])) {
            foreach($parameters['in'] as $in) {
                $in->foreign = $this->_default_table($in->foreign);
            }
        }
        $query = new PDOQuery(
            PDOQuery::Select,
            $this->_default_table(),
            $parameters
        );
        $sth = $this->_backend->prepare($query->sql());
        if($parameters['filters']) {
            foreach($parameters['filters'] as $filter) {
                if(is_int($filter->pattern)) {
                    $type = \PDO::PARAM_INT;
                } else {
                    $type = \PDO::PARAM_STR;
                }
                $sth->bindValue(':' . $filter->hash, $filter->pattern, $type);
            }            
        }
        $sth->execute();
        return new \Core\Li($sth->fetchAll(\PDO::FETCH_ASSOC));
    }

    public function save(\Core\Mapped $object) {
        if($object->id > 0) {
            $this->_update($object);
        } else {
            $this->_insert($object);   
        }
    }

    protected function _join_table_name() {
        
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

    public function delete($parameters=Null) {
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
    protected function _default_table($table=False) {
        if(!$table) {
            $table = $this->_class_name();
        }
        return strtolower($table) . 's';
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
        if($this->_parameters['lis']) {
            $this->_eval_lis();
        }
        return sprintf("%s %s %s %s",
            $this->_head(),
            $this->_joins(),
            $this->_filters(),
            $this->_orders()
        );
    }

    protected function _eval_lis() {
        foreach($this->_parameters['li'] as $li) {
        }
    }
    protected function _head() {
        if($this->_type == PDOQuery::Select) {
            if(!isset($this->_parameters['fields'])) {
                $fields = new \Core\Li($this->_table . '.*');
            } else {
                $fields = new \Core\Li($this->_parameters['fields']);
            }
            if($this->_parameters['joins']){
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
        $types = \Core\Utils\Language::plural($type);
        if(!$this->_parameters[$types]) {
            return False;
        }
        foreach ($this->_parameters[$types] as $item) {
            $f = "_{$type}_to_sql";
            $string .= ' ' . $this->$f($item);
        }
        return $string;
    }
    
    protected function _joins() {
        return $this->_get_lines('join');
    }
    
    private function _join_to_sql(\Core\Join $join) {
        $return = sprintf(' LEFT JOIN %1$s %3$s on %2$s.%3$s = %3$s.id',
            \Core\Storage\PDO::create($join->foreign)
                ->get_table_name(),
            $this->_table,
            $join->local
        );
        if($join->subjoins) {
            foreach($join->subjoins as $subjoin) {
                $return .= sprintf(' LEFT JOIN %1$s %3$s on %2$s.%4$s = %3$s.id',
                    \Core\Storage\PDO::create($subjoin->foreign)
                        ->get_table_name(),
                    $join->local,
                    $join->local . '_' . $subjoin->local,
                    $subjoin->local
                );
            }
        }
        return $return;
    }

    private function _single_join_fields($join, $parent=False) {
        $fields = new \Core\Li();
        if($join->fields) {
            foreach($join->fields as $field) {
                $fields->extend(sprintf('%1$s.%2$s as %1$s_%2$s',
                    ($parent ? $parent->local . '_' : null) . $join->local,
                    $field
                ));
            }
        }
        if(is_array($join->subjoins)) {
            foreach($join->subjoins as $subjoin) {
                $fields->extend($this->_single_join_fields($subjoin, $join));
            }
        }

        return $fields;
    }

    private function _join_fields($current=False, $parent=False) {
        $fields = new \Core\Li();
        foreach($joins = $this->_parameters['joins'] as $join) {
            $fields->extend($this->_single_join_fields($join));
          
        };
        return $fields;
    }

    protected function _in_table($table) {
        
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
        if(!$this->_parameters['filters']) {
            return False;
        }
        foreach ($this->_parameters['filters'] as $item) {
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


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
import('core.backend.hs');

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
    protected $_parameters;
    protected $_backend;

    protected static $_hs_index = 50;
    protected $_hs = False;

    public function __construct($args) {
        parent::__construct($args);
    }

    public function attach_backend($backend) {
        $this->_backend = $backend;
        return $this;
    }

    protected function _expand_singular_parameters($pars) {
        foreach(array('join', 'filter', 'in', 'order', 'field', 'bind') as $t) {
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
        $this->_parameters = $parameters;
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
        $binds = $this->_binds();
        if($parameters['filters']) {
            $binds = array_merge($binds, $this->_filter_binds($parameters['filters']));         
        }
        $sth->execute($binds);
        return new \Core\Li($sth->fetchAll(\PDO::FETCH_ASSOC));
    }

    protected function _filter_binds($filters) {
        $binds = array();
        foreach($filters as $filter) {
            if($filter->operand == 'in') {
                foreach($filter->hashes as $k => $hash){
                    $binds[':' . $hash] = $filter->pattern[$k];
                }
            } else if(!$filter->complex) {
                $binds[':' . $filter->hash] = $filter->pattern;
            }
        }   
        return $binds;
    }

    public function save(\Core\Mapped $object) {
        if($object->id > 0) {
            $this->_update($object);
        } else {
            $this->_insert($object);   
        }
        return $this;
    }

    protected function _binds($data=array()) {
        $binds = array();
        foreach($data as $field => $value) {
            $binds[':' . $field] = $value;
        }
        if(is_array($this->_parameters['binds'])) {
            foreach($this->_parameters['binds'] as $field => $value) {
                $binds[$field] = $value;
            }            
        }
        return $binds;
    }

    protected function _purify_input($data) {
        if(is_array($this->_parameters['binds'])) {
            $this->_parameters['binds'] = array_map(
                \Core\Storage\PDO::common_to_string($v),
                $this->_parameters['binds']
            );            
        }
        $ret = array_map(function($v) {
            return \Core\Storage\PDO::common_to_string($v);
        }, $data);
        return $ret;
    }
    public static function common_to_string($mixed) {
        if($mixed instanceof \DateTime) {
            return $mixed->format('Y-m-d H:i:s');
        }

        return $mixed;
    }

    protected function _insert(\Core\Mapped $object, $force_id=False) {
        $data = $this->_filter($object);
        $data = $this->_purify_input($data);

        $query = new PDOQuery(
            PDOQuery::Insert,
            $this->_default_table(),
            array(
                'data' => $data
            )
        );
        $sth = $this->_backend->prepare($query->sql());
        $sth->execute($this->_binds($data));
        $inserted = $this->_backend->lastInsertId('id');
        $object->id = $inserted;
        return $inserted;
    }
    
    protected function _update(\Core\Mapped $object) {
        $data = $this->_filter($object);
        $data = $this->_purify_input($data);
        $query = new PDOQuery(
            PDOQuery::Update,
            $this->_default_table(),
            array(
                'data' => $data
            )
        );
        $sth = $this->_backend->prepare($query->sql());

        $binds = $this->_binds($data);
        $binds[':id'] = (int)$object->id;
        $sth->execute($binds);
    }

    protected function _filter(\Core\Mapped $object) {
        $returns = array();
        $fields = $object->list_fields();
        if(!is_array($fields)) {
            throw new \Core\Error('Mapped object must have enumerable fields.');
        }
        foreach($object as $key => $value) {
            if(in_array($key, $fields)) {
                $returns[$key] = $value;
            }            
        }
        return $returns;
    }

    public function delete(\Core\Mapped $object) {
        if($object->id < 1) {
            throw new \Core\StorageError("Trying to delete non-existent object.");
        }
        $query = new PDOQuery(
            PDOQuery::Delete,
            $this->_default_table()
        );
        $sth = $this->_backend->prepare($query->sql());
        $sth->execute(array(
            ':id' => $object->id
        ));
        $object->id = '-1';
        return $this;
    }
    
    public function get_table_name() {
        return $this->_default_table();
    }
    
    /**
     * Create a table_name based on ClassName
     * TODO: Proper grammatical plurals, ala Django or SQLAlchemy
     */
    protected function _default_table($class=False) {
        if(!$class) {
            $class = $this->_class_name();
        }
        return strtolower($class).'s';
    }

}

class HSInsertFailedError extends \Core\StandardError {}
class PDOQuery {

    const Select = 'SELECT %1$s FROM %2$s';
    const Delete = 'DELETE FROM %s';
    const Update = 'UPDATE %s SET';
    const Insert = 'INSERT INTO %s';
    
    protected $_parameters;
    protected $_table;
    protected $_type;
    
    private $_filter_no = 0;
    public function __construct($type, $table, $parameters=False) {
        $this->_parameters = $parameters;
        $this->_table = $table;
        $this->_type = $type;
    }

    public function sql() {
        if($this->_parameters['lis']) {
            $this->_eval_lis();
        }
        switch($this->_type) {
            case PDOQuery::Select:
                return sprintf("%s %s %s %s",
                    $this->_head(),
                    $this->_joins(),
                    $this->_filters(),
                    $this->_orders()
                );
                break;
            case PDOQuery::Insert:
                return sprintf("%s\n(%s)\nVALUES (%s)",
                    $this->_head(),
                    $this->_insert_fields(),
                    $this->_insert_fields(':')
                );
                break;
            case PDOQuery::Update:
                return sprintf("%s\n%s\nWHERE id = :id",
                    $this->_head(),
                    $this->_update_fields()
                );
                break;
            case PDOQuery::Delete:
                return sprintf("%s\nWHERE id = :id",
                    $this->_head());
                break;
            
        }
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
                $fields = new \Core\Li($this->_table . '.*', $this->_parameters['fields']);
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
        } else {
            $fields->extend(sprintf("%s.*", $join->local));
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
    protected function _insert_fields($prefix=null) {
        $sqls = array();
        foreach($this->_parameters['data'] as $key => $value) {
            $sqls[] = $prefix . $key;
        }
        return implode(",", $sqls);
    }
    
    protected function _update_fields() {
        $sqls = array();
        foreach($this->_parameters['data'] as $key => $value) {
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
        foreach ($this->_parameters['filters'] as $filter) {
            $string .= ($i > 0 ? ' AND ' : 'WHERE ') . $this->_filter_to_sql($filter);
            $i++;
        }
        return $string;
    }
    
    public function _filter_to_sql(\Core\Filter $filter) {
        if($filter->complex) {
            return $filter->complex_text;
        }
        if($filter->explicit) {
            $field = $filter->field;
        } else {
            $field = $this->_table . '.' . $filter->field;
        }
        if($filter->operand == 'in') {
            $prefixed = array_map(function($v) {
                return ':'.$v;
            }, $filter->hashes);
            $collapsed = implode(',', $prefixed);
            return sprintf("%s in(%s)",
                $field,
                $collapsed
            );
        }
        $return = sprintf("%s %s %s",
            $field,
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


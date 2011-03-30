<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Backend;

import('core.backend');
import('core.exceptions');

class HS extends \Core\Contained {
    private static $_hs_index = 0;
    protected $_handlersocket;
    protected $_handlersocket_wr;
    private $_db;

    public function set_db($db) {
        $this->_db = $db;
        return $this;
    }
    public function set_handlersocket($handlersocket, $handlersocket_wr) {
        $this->_handlersocket = $handlersocket;
        $this->_handlersocket_wr = $handlersocket_wr;
        return $this;
    }

    public function get($key, $class) {
        $index = $this->_open_index($class); 
        $data = $this->_handlersocket->executeSingle($index, '=', array($key), 1, 0);
        return $this->_make_assoc($data, $class::$fields);
    }

    /**
     * Set key for class with values.
     * MUST be complete record.
     */
    public function set($key, $class, $values) {
        $index = $this->_open_index_wr($class, False, '');
        $values = $this->_filter_values($class, $values);
        $was_set = $this->_handlersocket_wr->executeUpdate(
            $index,
            '=',
            array($key),
            $values,
            1, 0
        );
        if(!$was_set) {
            throw new HSError("Failed to set {$key}: " . $this->_handlersocket_wr->getError());
        }
    }

    public function add($key, $class, $values) {
        if(!is_array($values)) {
            throw new HSError("Trying to add to HS without values");
        }
        $index = $this->_open_index_wr($class, True, '');
        $values = $this->_filter_values($class, $values);
        array_unshift($values, $key);
        $was_set = $this->_handlersocket_wr->executeInsert(
            $index,
            $values
        );
        if(!$was_set) {
            var_dump("a", $index, $values);
            throw new HSError("Failed to add {$key}: " . $this->_handlersocket_wr->getError());
            die();
        }
    }

    public function delete($key, $class) {
        $index = $this->_open_index_wr($class, False, '');
        $was_delete = $this->_handlersocket_wr->executeDelete(
            $index,
            '=',
            array($key)
        );
        if(!$was_delete) {
            throw new HSError("Failed to delete {$key}: " . $this->_handlersocket_wr->getError());
        }
    }
    protected function _filter_values($class, $values) {
        $rtn = array();
        foreach($class::$fields as $field) {
            array_push($rtn, $values[$field]);
        }
        return $rtn;
    }

    protected function _open_index($class, $prepend_id=False, $primary=\HandlerSocket::PRIMARY, $wr=False) {
        $index = self::$_hs_index++;
        $fields = implode(',', $class::$fields);
        if($prepend_id) {
            $fields = 'id,' . $fields;
        }
        if($wr) {
           $hs = $this->_handlersocket_wr;        
        }
        else {
            $hs = $this->_handlersocket;
        }
        $hs->openIndex(
            $index,
            $this->_db,
            $class::table_name(),
            $primary,
            $fields
        );
        return $index;
    }
    protected function _open_index_wr($class, $prepend_id=False, $primary=\HandlerSocket::PRIMARY) {
        return $this->_open_index($class, $prepend_id, $primary, True);
    }

    protected function _make_assoc($data, $fields) {
        $assoc = array();
        foreach($fields as $k => $field) {
            $assoc[$field] = $data[0][$k];
        }
        return $assoc;

    }
}

class HSContainer extends \Core\BackendContainer {
    protected static $_default_connection = False;
    public function get_backend($write=False) {
        if(!class_exists('\HandlerSocket')) {
            throw new HSNotLoadedError();
        }
        $this->_load_config();
        $this->_check_config();
        if(static::$_default_connection) {
            return static::$_default_connection;
        } 
        $conf = $this->_config['handlersocket'];

        $handlersocket = new \HandlerSocket(
            $conf['host'],
            $conf['port']
        );
        $handlersocket_wr = new \HandlerSocket(
            $conf['host'],
            $conf['port_wr']
        );

        $hs = HS::create()
            ->set_handlersocket($handlersocket, $handlersocket_wr)
            ->set_db($this->get_db_name());
        static::$_default_connection = $hs;
        return $hs;
    }

    public function get_db_name() {
        $this->_load_config();
        $this->_check_config();
        $conf = $this->_config['database'];
        return $conf['database'];
    }
}

class HSNotLoadedError extends \Core\StandardError {}
class HSError extends \Core\Error {}
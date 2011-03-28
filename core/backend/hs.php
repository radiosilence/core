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

class HS extends \Core\Contained {}

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
        $hs = new \HandlerSocket(
            $conf['host'],
            ($write ? $conf['port_wr'] : $conf['port'])
        );
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
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

class Memcached extends \Core\Contained {}

class MemcachedContainer extends \Core\BackendContainer {
    protected static $_default_connection = False;
    public function get_backend() {
        if(static::$_default_connection instanceof \Memcached) {
            return static::$_default_connection;
        }
        $this->_load_config();
        $this->_check_config();

        $m = new \Memcached();
        foreach($this->_config['memcached'] as $server) {
            $m->addServer($server['host'], $server['port']);
        }
        static::$_default_connection = $m;
        return $m;
    }
}

<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Utils;

import('core.backend.memcached');
import('core.utils.env');
import('core.utils.ipv4');
import('core.auth');

class Throttle {
    const Second = 1;
    const Minute = 60;
    const Hour = 3600;
    const Day = 86400;

    protected $_timers = array();

    public function __construct($timers, $id=False) {
        $this->_memcached = \Core\Backend\Memcached::container()
            ->get_backend();

        if(!$id) {
            $id = \Core\Utils\IPv4::get();
        }
        $this->_timers = $timers;
        $this->_id = $id;
        $this->_incr();
    }

    protected function _incr() {
        $m = $this->_memcached;
        foreach($this->_timers as $type => $limit) {
            $k = $this->_key($type);
            if(!$m->get($k)) {
                $m->set($k, 1, $type);
                continue;
            }
            $m->increment($k);
            if($m->get($k) > $limit) {
                throw new TooManyReqsError();
            }
        }
    }

    protected function _key($type) {
        return sprintf("site:%s:throttle:%s:%s",
            \Core\Utils\Env::site_name(),
            $this->_id,
            $type);
    }
}

class TooManyReqsError extends \Core\StandardError {}
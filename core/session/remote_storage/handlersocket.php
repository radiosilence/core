<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Session\RemoteStorage;

import('core.session.remote_storage.exceptions');
import('core.session.interfaces');
import('core.dependency');
import('core.types');
import('core.backend.hs');
import('core.utils.env');


class HandlerSocket extends \Core\Dict implements \Core\Session\RemoteStorage {
    /**
     * Untrusted session details.
     */
    private $_untrusted = array();
    /**
     * Actual trusted session details.
     */
    private $_actual = array();
    /**
     * Found session
     */
    private $found_session;
    
    /**
     * Remote address
     */
    private $_remote_addr;

    /**
     * The class to use for insertion.
     */
    private static $_mapped_class = '\Core\Session\RemoteStorage\Session';
    /**
     * Memcached object.
     */
    private $_hs;
    /**
     * Expiration time.
     */
    private $_exp = 604800;
    /**
     * Set remote address.
     */
    public function set_remote_addr($remote_addr) {
        $this->_remote_addr = $remote_addr;
        return $this;
    }

    public function set_data($data) {
        $this->__data__ = $data;
    }

    public function attach_hs($hs) {
        $this->_hs = $hs;
    }

    protected function _key($sid, $tok) {
        return sprintf('%s:%s', $sid, $tok);
    }

    /**
     * Inserts a new session into the database.
     * @param integer $user_id
     * @return boolean success
     */
    public function add($actual) {
        $this->_hs->add(
            $this->_key($actual['sid'], $actual['tok']),
            static::$_mapped_class,
            array(
                'data' => json_encode($this->__data__),
                'remote_addr' => $this->_remote_addr
            )
        );
    }
 
    /**
     * Find a matching sid/tok/IP in the database
     */
    public function load($untrusted) {
        $this->_untrusted = $untrusted;
        $this->find_session();
        $this->_actual = $untrusted;
        $this->decode_found_data();
    }

    /**
     * Makes the session in the database have the current data.
     */
    public function save() {
        $this->_hs->set(
            $this->_key($this->_actual['sid'], $this->_actual['tok']),
            static::$_mapped_class,
            array(
                'data' => json_encode($this->__data__),
                'remote_addr' => $this->_remote_addr
            )
        );    
    }

    /**
     * Destroys sid in database
     */
    public function destroy() { 
        $this->_hs->delete($this->_key($actual['sid'], $actual['tok'], 'data'));
        $this->_hs->delete($this->_key($actual['sid'], $actual['tok'], 'remote_addr'));
    }

    private function find_session() {
        $fetched = $this->_hs->get(
            $this->_key($this->_untrusted['sid'], $this->_untrusted['tok']),
            static::$_mapped_class
        );
        if(empty($fetched['remote_addr'])) {
            throw new SessionNotFoundError();
        }
        $this->found_session = array(
            'sid' => $this->_untrusted['sid'],
            'tok' => $this->_untrusted['tok'],
            'data' => $fetched['data'],
            'remote_addr' => $fetched['remote_addr']
        );
    }

    private function decode_found_data() {
        $this->found_session['data'] = json_decode($this->found_session['data'], True);
        $this->__data__ = $this->found_session['data'];
    }
}

class Session extends \Core\Mapped {
    public static $fields = array('data', 'remote_addr');
}
?>

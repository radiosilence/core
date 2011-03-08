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
import('core.backend.memcached');
import('core.utils.env');

class Memcached extends \Core\Dict implements \Core\Session\RemoteStorage {
    /**
     * Untrusted session details.
     */
    private $untrusted = array();
    /**
     * Actual trusted session details.
     */
    private $actual = array();
    /**
     * Found session
     */
    private $found_session;
    
    /**
     * Remote address
     */
    private $remote_addr;

    /**
     * Memcached object.
     */
    private $_mc;
    /**
     * Expiration time.
     */
    private $_exp = 604800;
    /**
     * Set remote address.
     */
    public function set_remote_addr($remote_addr) {
        $this->remote_addr = $remote_addr;
        return $this;
    }

    public function set_data($data) {
        $this->__data__ = $data;
    }

    public function attach_mc($mc) {
        $this->_mc = $mc;
    }

    protected function _key($sid, $tok, $field) {
        return sprintf('session:%s:%s:%s:%s', \Core\Utils\Env::site_name(), $sid, $tok, $field);
    }

    /**
     * Inserts a new session into the database.
     * @param integer $user_id
     * @return boolean success
     */
    public function add($actual) {
        $this->_mc->set(
            $this->_key($actual['sid'], $actual['tok'], 'data'),
            json_encode($this->__data__),
            $this->_exp
        );

        $this->_mc->set(
            $this->_key($actual['sid'], $actual['tok'], 'remote_addr'),
            $this->_remote_addr,
            $this->_exp
        );
    }
 
    /**
     * Find a matching sid/tok/IP in the database
     */
    public function load($untrusted) {
        $this->untrusted = $untrusted;
        $this->find_session();
        $this->actual = $untrusted;
        $this->decode_found_data();
    }

    /**
     * Makes the session in the database have the current data.
     */
    public function save() {
        $this->add($this->actual);
    }

    /**
     * Destroys sid in database
     */
    public function destroy() { 
        $this->_mc->delete($this->_key($actual['sid'], $actual['tok'], 'data'));
        $this->_mc->delete($this->_key($actual['sid'], $actual['tok'], 'remote_addr'));
    }

    private function find_session() {
        $data = $this->_mc->get(
            $this->_key($this->untrusted['sid'], 
            $this->untrusted['tok'], 'data')
            );
        if(empty($data)) {
            throw new SessionNotFoundError();
        }
        $ip = $this->_mc->get(
            $this->_key($this->untrusted['sid'], 
            $this->untrusted['tok'], 'remote_addr'));

        $this->found_session = array(
            'sid' => $this->untrusted['sid'],
            'tok' => $this->untrusted['tok'],
            'data' => $data,
            'remote_addr' => $ip
        );
    }

    private function decode_found_data() {
        $this->found_session['data'] = json_decode($this->found_session['data'], True);
        $this->__data__ = $this->found_session['data'];
    }
}

?>

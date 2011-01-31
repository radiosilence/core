<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */
 
namespace Core\Security;

import('core.exceptions');

class AntiXSRF extends \Core\Dict {
    protected $_session;
    protected $_auto;
    protected $_hash;
    protected $_test_hash;

    protected $_session_attached = False;

    public function __construct($test_hash) {
        $this->_test_hash = $test_hash;
        $this->_generate_hash();
    }

    public function attach_session(\Core\Session\Handler $session) {
        $this->_session = $session;
        $this->_session_attached = True;
        $this->_previous_hash = $this->_session->__hash__;
        $this->_session->__hash__ = $this->_hash;
        return $this;
    }

    public function check() {
        $this->_compare_hashes();
        return $this;
    }
    public function get_hash() {
        return $this->_hash;
    }

    protected function _generate_hash() {
        $this->_hash = (string)mt_rand();
    }

    protected function _compare_hashes() {
        if($this->_previous_hash != $this->_test_hash) {
            throw new \Core\HTTPError(401);
        }
    }
}

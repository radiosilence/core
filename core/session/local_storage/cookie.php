<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace Core\Session\LocalStorage;

import('core.session.local_storage.exceptions');
import('core.session.storage');
import('core.dependency');

\Core\DEPENDENCY::require_functions('setcookie');

class Cookie implements \Core\Session\LocalStorage {
    private $untrusted = array();
    private $actual = array();
    private $cookie;
    
    public function __construct($cookie=False) {
        if(empty($cookie)) {
            $cookie = $_COOKIE;
        }
        foreach(array('sid','tok') as $key) {
            $this->untrusted[$key] = $cookie[$key];
        }
    }
    
    public function get() {
        if(empty($this->untrusted['sid']) || empty($this->untrusted['sid'])) {
            throw new CookieNotSetError();
        }

        return $this->untrusted;
    }

    /**
     * Sets the cookies, with httponly.
     */
    public function set($actual) {
        $this->actual = $actual;
        if(!is_array($this->actual)) {
            return False;
        }
        foreach($this->actual as $key => $value) {
            setcookie($key, $value, time()+(3600*24*65), null, null, false, true);
        }
    }

    public function __destruct() {
    }
    
    /**
     * Destroys sid and tok cookies.
     */
    public function destroy() {
        $this->actual['sid'] = null;
        $this->actual['tok'] = null;
    }
}

class CookieNotSetError extends Error {
    public function __construct() {
        parent::__construct("Cookie not set.");
    }
}
?>
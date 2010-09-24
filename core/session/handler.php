<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace Core\Session;

import('core.session.interfaces');
import('core.exceptions');

class Handler {
    /**
     * Untrusted session details.
     */
    private $untrusted = array();
    /**
     * Actual trusted session details.
     */
    private $actual = array();
    /**
     * Secret phrase
     */
    private $keyphrase;
    /**
     * Secret phrase to salt passwords
     */
    private $base_salt;
    /**
     * Remote address of client
     */
    private $remote_addr;
    /** 
     * Remote storage.
     */
    private $remote_storage;
    /**
     * Local storage.
     */
    private $local_storage;
    
    /**
     * Give it an IP.
     * @param $addr IP Address
     */
    public function set_remote_addr($addr) {
        $this->remote_addr = $addr;
        $this->remote_storage->set_remote_addr($addr);
        return $this;
    }
    
    public function attach_local_storage($local_storage) {
        $this->local_storage = $local_storage;
        return $this;
    }
    
    public function attach_remote_storage($remote_storage) {
        $this->remote_storage = $remote_storage;
        $this->remote_storage->set_remote_addr($this->remote_addr);
        return $this;
    }

    public function attach_crypto_config($file=False) {
        if(empty($file)) {
            $file = SITE_PATH . 'config' . DIRSEP . 'crypto.php';
        }
        if(!file_exists($file)) {
            throw new \Core\FileNotFoundError($file);    
        }
        require_once($file);
        $this->keyphrase = $config_auth['keyphrase'];
        $this->base_salt = $config_auth['base_salt'];
        return $this;
    }

    /**
     * Error checking thing to make sure the necessary things have been done.
     */
    private function check_setup() {
        if(empty($this->remote_addr)) {
            throw new SessionSetupIncompleteError("Remote address not set.");
        }   
        if(!is_object($this->local_storage)) {
            throw new SessionSetupIncompleteError("Local storage not attached.");
        }
        if(!is_object($this->remote_storage)) {
            throw new SessionSetupIncompleteError("Remote storage not attached.");
        }
        if(empty($this->keyphrase) || empty($this->base_salt)) {
            throw new SessionSetupIncompleteError("Cryptographic configuration incomplete.");
        }
    }
    
    /**
     * Starts it all off, gets the sid/tok provided by
     * the cookie, and authorises it/registers it as
     * valid depending on the result.
     */
    public function start() {
        try {
            $this->check_setup();
            $this->detect_existing_session();
            $this->set_session();
            return $this;
        } catch(SessionNotFoundError $e) {
            echo "SESSION NOT FOUND ERROR";
            //$this->local_storage->destroy();
            return False;
        } catch(CookieNotSetError $e) {
            return False;
        } catch(TokenMismatchError $e) {
            echo "TOKEN MISMATCH";
            $this->local_storage->destroy();
            return False;
        }
    }

    /**
     * Creates a session, puts it in the database,
     * returns the ID.. Assumes login has succeeded.
     * @param integer $user_id User ID
     * @return array Either a fail or an array with $sid, $id, and $tok.
     */
    public function create() {
        $this->generate_session();
        try {
            $this->remote_storage->add($this->actual);
            $this->local_storage->set($this->actual);
        } catch(SessionRemoteStorageError $e) {
            print $e->getMessage();
        }
    }

    /**
     * Destroys the session, deletes from DB, unsets cookies.
     */
    public function destroy() {
        try {
            $this->remote_storage->destroy();        
            $this->local_storage->destroy();       
        } catch(SessionRemoteStorageError $e) {
            return False;
        }
    }
    
    public function __destruct() {
        if(!empty($this->actual['sid'])) {
            try {
                $this->remote_storage->save();
            } catch(SessionRemoteStorageError $e) {
                return False;
            }
        }
    }

    /**
     * Gets stuff from data, overloader.
     * @param $key Property
     * @return boolean
     */
    public function __get($key) {
        if (isset($this->remote_storage->$key)) {
            return $this->remote_storage->$key;
        } else {
            return false;
        }
    }

    /**
     * Sets stuff to data, overloader.
     * @param $key Property
     * @param $value Property data
     * @return boolean
     */
    public function __set($key, $value) {
        $this->remote_storage->$key = $value;
        return true;
    }

    private function detect_existing_session() {
            $this->read_local_storage();
            $this->remote_storage->load($this->untrusted);
            $this->test_token();
    }

    private function generate_session() {
        $this->actual['sid'] = $this->create_sid();
        $this->actual['tok'] = $this->create_token($this->actual['sid']);
    }

    /**
     * Regenerate token and compare to the cookie.
     */
    private function test_token() {
        $chall = $this->create_token($this->untrusted['sid']);
        if($chall != $this->untrusted['tok']) {
            throw new TokenMismatchError();
        }
    }

    private function read_local_storage() {
        $this->untrusted = $this->local_storage->get();
    }

    /**
     * Sets the object's session to the right things.
     */
    private function set_session() {
        $this->actual = $this->untrusted;
    }

    /**
     * Generates a new auth token based on session ID.
     * @param string $passhash Password hash.
     * @param string $email User's email.
     */
    private function create_token($tok) {
        # Token generation code.
        $hash = sha1($this->keyphrase . $this->remote_addr . $tok);
        return $hash;
    }

    /**
     * Generate a simple sid hash.
     * @return hash sid
     */
    private function create_sid() {
        return sha1(microtime() . $this->remote_addr);
    }
}

class SessionRemoteStorageError extends \Core\Error {
    public function __construct($message) {
        parent::__construct(sprintf("Session error [Remote]: %s", $message));
    }
}

class SessionLocalStorageError extends \Core\Error {
    public function __construct($message) {
        parent::__construct(sprintf("Session error [Local]: %s", $message));
    }
}

class TokenMismatchError extends \Core\Error {}

class SessionSetupIncompleteError extends \Core\Error {
    public function __construct($message) {
        parent::__construct(sprintf("Session setup incomplete: %s", $message));
    }
}
?>
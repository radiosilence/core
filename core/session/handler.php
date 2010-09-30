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

import('core.session.storage');
import('core.exceptions');

class Handler {
    /**
     * Actual and untrusted session details.
     */
    /**
     * @var array
     */
    private $untrusted = array();

    /**
     * @var array
     */
    private $actual = array();

    /**
     * @var string
     */
    private $keyphrase;

    /**
     * @var string
     */
    private $base_salt;

    /**
     * @var string
     */
    private $remote_addr;

    /**
     * @var \Core\Session\RemoteStorage
     */
    private $remote_storage;

    /**
     * @var \Core\Session\LocalStorage
     */
    private $local_storage;
    
    public function set_remote_addr($addr) {
        $this->remote_addr = $addr;
        return $this;
    }

    public function initialize_remote_storage() {
        $attached = $this->remote_storage instanceof \Core\Session\RemoteStorage;
        if(!$attached) {
            throw new RemoteStorageNotAttachedError();
        }

        $this->remote_storage->set_remote_addr($addr);
        return $this;
    }
    
    public function attach_local_storage($local_storage) {
        $this->local_storage = $local_storage;
        return $this;
    }
    
    public function attach_remote_storage($remote_storage) {
        $this->remote_storage = $remote_storage;
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
            throw new SetupIncompleteError("Remote address not set.");
        }   
        if(!($this->local_storage instanceof \Core\Session\LocalStorage)) {
            throw new LocalStorageNotAttachedError();
        }
        if(!($this->remote_storage instanceof \Core\Session\RemoteStorage)) {
            throw new RemoteStorageNotAttachedError();
        }
        if(empty($this->keyphrase) || empty($this->base_salt)) {
            throw new SetupIncompleteError("Cryptographic configuration incomplete.");
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
        } catch(RemoteStorage\SessionNotFoundError $e) {
            $this->local_storage->destroy();
        } catch(LocalStorage\CookieNotSetError $e) {
            // Don't care.
        } catch(TokenMismatchError $e) {
            // Delete incase of tampering.
            $this->local_storage->destroy();
        }
        $this->create();
        return $this;
    }

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
            return "FAILED TO DESTROY";
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
        return $this->remote_storage->$key;
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

class TokenMismatchError extends \Core\Error {}

class SetupIncompleteError extends \Core\Error {
    public function __construct($message) {
        parent::__construct(sprintf("Session setup incomplete: %s", $message));
    }
}

class RemoteStorageNotAttachedError extends SetupIncompleteError {
    public function __construct() {
        parent::__construct("Remote storage required but not attached.");
    }
}

class LocalStorageNotAttachedError extends SetupIncompleteError {
    public function __construct() {
        parent::__construct("Local storage required but not attached.");
    }
}
?>
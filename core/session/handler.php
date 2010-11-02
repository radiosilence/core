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

class Handler extends \Core\Contained {
    private $untrusted = array();
    private $actual = array();
    private $keyphrase;
    private $base_salt;
    private $remote_addr;
    private $remote_storage;
    private $local_storage;
    
    /**
     * Set the remote address.
     * 
     * @param string $addr
     */
    public function set_remote_addr($addr) {
        $this->remote_addr = $addr;
        return $this;
    }

    /**
     * Checks if the remote storage is attached then calls any setter functions on it.
     */
    public function initialize_remote_storage() {
        $attached = $this->remote_storage instanceof RemoteStorage;
        if(!$attached) {
            throw new RemoteStorageNotAttachedError();
        }
	
        $this->remote_storage->set_remote_addr($this->remote_addr);
        return $this;
    }
    
    /**
     * Attach a local storage instance.
     * 
     * @param \Core\Session\LocalStorage $local_storage
     */ 
    public function attach_local_storage(LocalStorage $local_storage) {
        $this->local_storage = $local_storage;
        return $this;
    }
    
    /**
     * Attach remote storage instance.
     * 
     * @param \Core\Session\RemoteStorage $remote_storage
     */ 
    public function attach_remote_storage(RemoteStorage $remote_storage) {
        $this->remote_storage = $remote_storage;
        return $this;
    }

    /**
     * Attach and load a file with cryptographic information.
     * 
     * TODO: Make it not use a shitty array.
     * 
     * @param string $file
     */
    public function attach_crypto_config($config) {
        $this->keyphrase = $config['keyphrase'];
        $this->base_salt = $config['base_salt'];
        return $this;
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
        $this->initialise();
        return $this;
    }

    /**
     * Destroys the session, deletes from remote and local..
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
            } catch(RemoteStorage\Error $e) {
                return False;
            }
        }
    }

    public function __get($key) {
        return $this->remote_storage->$key;
    }

    public function __set($key, $value) {
        $this->remote_storage->$key = $value;
        return true;
    }

    private function initialise() {
        $this->generate_session();
        try {
            $this->remote_storage->add($this->actual);
            $this->local_storage->set($this->actual);
        } catch(RemoteStorage\Error $e) {
            print $e->getMessage();
        }
    }
    
    private function check_setup() {
        if(empty($this->remote_addr)) {
            throw new SetupIncompleteError("Remote address not set.");
        }   
        if(!($this->local_storage instanceof LocalStorage)) {
            throw new LocalStorageNotAttachedError();
        }
        if(!($this->remote_storage instanceof RemoteStorage)) {
            throw new RemoteStorageNotAttachedError();
        }
        if(empty($this->keyphrase) || empty($this->base_salt)) {
            throw new SetupIncompleteError("Cryptographic configuration incomplete.");
        }
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
    private function create_token($sid) {
        # Token generation code.
        $hash = sha1($this->keyphrase . $this->remote_addr . $sid);
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

class HandlerContainer extends \Core\ConfiguredContainer {

    public function get_standard_session() {

        import('core.session.handler');
        import('core.session.remote_storage.pdo');
        import('core.session.local_storage.cookie');
        import('core.utils.ipv4');

        $sh = new Handler();
        $srp = new RemoteStorage\PDO();
        $slc = new LocalStorage\Cookie();

        $srp->attach_pdo($pdo);
        $this->load_config('crypto');

        try{
            $sh->attach_remote_storage($srp)
                ->attach_local_storage($slc)
                ->attach_crypto_config($this->parameters['config_crypto'])
                ->set_remote_addr(\Core\Utils\IPV4::get())
                ->initialize_remote_storage()
                ->start();
            return $sh;
        } catch(Core\Error $e) {
            echo "SERIOUSLY AN ERROR";
            // Colossal failure.
            return False;   
        }
    }
}

class TokenMismatchError extends \Core\Error {
    public function __construct() {
        parent::__construct('Token mismatch error, deleting cookies.');
    }
}

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

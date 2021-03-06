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
import('core.utils.bots');

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

    public function get_tok() {
        return $this->actual['tok'];
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
            $this->_check_setup();
            $this->_detect_existing_session();
            $this->_set_session();
            return $this;
        } catch(RemoteStorage\SessionNotFoundError $e) {
            $this->local_storage->destroy();
        } catch(LocalStorage\CookieNotSetError $e) {
            // Don't care.
        } catch(TokenMismatchError $e) {
            // Delete incase of tampering.
            $this->local_storage->destroy();
        }
        $this->_initialise();
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
        if(!empty($this->actual['sid']) and !\Core\Utils\Bots::is_bot()) {
            try {
                $this->remote_storage->set_data($this->__data__);
                $this->remote_storage->save();
            } catch(RemoteStorage\Error $e) {
                return False;
            }
        }
    }

    private function _initialise() {
        $this->_generate_session();
        try {
            $this->remote_storage->add($this->actual);
            $this->local_storage->set($this->actual);
        } catch(RemoteStorage\Error $e) {
            print $e->getMessage();
        }
    }
    
    private function _check_setup() {
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

    private function _detect_existing_session() {
            $this->_read_local_storage();
            $this->remote_storage->load($this->untrusted);
            $this->_test_token();
    }

    private function _generate_session() {
        $this->actual['sid'] = $this->_create_sid();
        $this->actual['tok'] = $this->_create_token($this->actual['sid']);
    }

    /**
     * Regenerate token and compare to the cookie.
     */
    private function _test_token() {
        $chall = $this->_create_token($this->untrusted['sid']);
        if($chall != $this->untrusted['tok']) {
            throw new TokenMismatchError();
        }
    }

    private function _read_local_storage() {
        $this->untrusted = $this->local_storage->get();
    }

    /**
     * Sets the object's session to the right things.
     */
    private function _set_session() {
        $this->actual = $this->untrusted;
        $this->__data__ = $this->remote_storage->__array__();
    }

    /**
     * Generates a new auth token based on session ID.
     * @param string $passhash Password hash.
     * @param string $email User's email.
     */
    private function _create_token($sid) {
        # Token generation code.
        $hash = sha1($this->keyphrase . $this->remote_addr . $sid);
        return $hash;
    }

    /**
     * Generate a simple sid hash.
     * @return hash sid
     */
    private function _create_sid() {
        return sha1(microtime() . $this->remote_addr);
    }
}

class HandlerContainer extends \Core\ConfiguredContainer {

    public function get_pdo_session() {
        import('core.utils.ipv4');
        return $this->_pdo_session(\Core\Utils\IPV4::get());
    }

    protected function _pdo_session($ip) {

        import('core.session.handler');
        import('core.session.remote_storage.pdo');
        import('core.session.local_storage.cookie');
        import('core.backend');

        $sh = new Handler();
        $srp = new RemoteStorage\PDO();
        $slc = new LocalStorage\Cookie();

        $srp->attach_pdo(\Core\Backend::container()
            ->get_backend());
        $this->_load_config();

        try{
            $sh->attach_remote_storage($srp)
                ->attach_local_storage($slc)
                ->attach_crypto_config($this->_config['crypto'])
                ->set_remote_addr($ip)
                ->initialize_remote_storage()
                ->start();
            return $sh;
        } catch(Core\Error $e) {
            echo "SERIOUSLY AN ERROR";
            // Colossal failure.
            return False;   
        }
    }


    public function get_mc_session() {
        import('core.utils.ipv4');
        return $this->_mc_session(\Core\Utils\IPV4::get());
    }

    public function get_anon_mc_session() {
        return $this->_mc_session('U MAD?');
    }

    protected function _mc_session($ip) {
        import('core.session.handler');
        import('core.session.remote_storage.memcached');
        import('core.session.local_storage.cookie');

        $sh = new Handler();
        $srp = new RemoteStorage\Memcached();
        $slc = new LocalStorage\Cookie();

        $srp->attach_mc(\Core\Backend\Memcached::container()
            ->get_backend()
        );

        $this->_load_config();

        try{
            $sh->attach_remote_storage($srp)
                ->attach_local_storage($slc)
                ->attach_crypto_config($this->_config['crypto'])
                ->set_remote_addr($ip)
                ->initialize_remote_storage()
                ->start();
            return $sh;
        } catch(Core\Error $e) {
            echo "SERIOUSLY AN ERROR";
            // Colossal failure.
            return False;   
        }
    }

    public function get_hs_session() {
        import('core.session.handler');
        import('core.session.remote_storage.handlersocket');
        import('core.session.local_storage.cookie');
        import('core.utils.ipv4');

        $sh = new Handler();
        $srp = new RemoteStorage\HandlerSocket();
        $slc = new LocalStorage\Cookie();

        $srp->attach_hs(\Core\Backend\HS::container()
            ->get_backend()
        );

        $this->_load_config();
        try{
            $sh->attach_remote_storage($srp)
                ->attach_local_storage($slc)
                ->attach_crypto_config($this->_config['crypto'])
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

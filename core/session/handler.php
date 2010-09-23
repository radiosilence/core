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

class Handler {
    /**
     * Session sid
     */
    public $sid;
    /**
     * Session token
     */
    private $tok;
    /**
     * Secret phrase
     */
    private $keyphrase;
    /**
     * Secret phrase to salt passwords
     */
    private $base_salt;
    /**
     * PDO statement for a correct sid and tok pair.
     */
    private $correct_session;
    /**
     * Session found in DB
     */
    private $found_session;
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
     * Attach a PDO. NECESSARY!
     * @param \PDO $pdo
     */
    
    public function set_remote_addr($addr) {
        $this->remote_addr = $addr;
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

    public function attach_auth_config($file=False) {
        if(empty($file)){
            $file = SITE_PATH . "config" . DIRSEP . "auth.php";
        }
        if(!file_exists($file)) {
            throw new FileNotFoundError($file);    
        }
        require_once($file);
        $this->keyphrase = $config_auth["keyphrase"];
        $this->base_salt = $config_auth["base_salt"];
        return $this;
    }
    
    /**
     * Starts it all off, gets the sid/tok provided by
     * the cookie, and authorises it/registers it as
     * valid depending on the result.
     * @param database $pdo database object.
     */
    public function start() {
        try {
        	$remote_storage->find();
            update_remote_storage();
            $this->test_token();
            $this->set_session();
            return $this;
        } catch(SessionFindError $e) {
            return False;
        } catch(TokenMismatchError $e) {
            $this->destroy_cookies();
            return False;
        }
    }

    /**
     * Creates a session, puts it in the database,
     * returns the ID.. Assumes login has succeeded.
     * @param integer $user_id User ID
     * @return array Either a fail or an array with $sid, $id, and $tok.
     */
    public function create($data=False) {
        $this->sid = $this->create_sid();
        $this->tok = $this->create_token($this->sid);
        $this->data = $data;
        try {
            $this->insert_new_session_into_db();
            $this->set_cookie();
        }
        catch(SessionInsertError $e){
            print $e->error_message;
        }
    }

    /**
     * Destroys the session, deletes from DB, unsets cookies.
     * 
     */
    public function destroy() {
        try {
            $this->delete_current_session_from_db();        
            $this->destroy_cookies();        
        } catch(SessionDeleteError $e) {
            return False;
        }
    }
    
    public function __destruct() {
        if($this->sid) {
            try {
                $this->update_session_in_db();
            } catch(SessionUpdateError $e) {
                return False;
            }
        }
    }

    /**
     * Gets stuff from data, overloader.
     * @param $prop_name Property
     * @param $prop_value Property data
     * @return boolean
     */
    public function __get($prop_name) {
        if (isset($this->remote_storage->data[$prop_name])) {
            return $this->remote_storage->data[$prop_name];
        } else {
            return false;
        }
    }

    /**
     * Sets stuff to data, overloader.
     * @param $prop_name Property
     * @param $prop_value Property data
     * @return boolean
     */
    public function __set($prop_name, $prop_value) {
        $this->remote_storage->[$prop_name] = $prop_value;
        return true;
    }

    public function var_dump() {
        var_dump($this);
    }
    /**
     * Regenerate token and compare to the cookie.
     */
    private function test_token(){
        $chall = $this->create_token($this->cookie_sid);
        if($chall != $this->cookie_tok) {
            throw new TokenMismatchError();
        }
    }

    /**
     * Makes the session in the database have the current data.
     */
    private function update_session_in_db() {
        $sth = $this->pdo->prepare("
            UPDATE sessions
            SET data = :data
            WHERE sid = :sid
        ");
        $ok = $sth->execute(array(
            "data" => json_encode($this->data),
            "sid" => $this->sid
        ));
        if(!$ok) {
            throw new SessionUpdateError();
        }

    }

    /**
     * Sets the object's session to the right things.
     */
    private function set_session() {
        $this->sid = $this->cookie_sid;
        $this->tok = $this->cookie_tok;
    }

    /**
     * Sets the cookies, with httponly.
     */
    private function set_cookie() {
        setcookie("sid", $this->sid, time()+(3600*24*65), null, null, false, true);
        setcookie("tok", $this->tok, time()+(3600*24*65), null, null, false, true);
    }

    /**
     * Generates a new auth token based on session ID.
     * @param string $passhash Password hash.
     * @param string $email User's email.
     */
    private function create_token() {
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

class SessionRemoteError extends Error {
    public $error_message;
    public function __construct($message) {
        $error_info = $message;
        parent::__construct();
    }
}

class SessionUpdateError extends SessionRemoteError {}
class SessionInsertError extends SessionRemoteError {}
class SessionDeleteError extends SessionRemoteError {}
class SessionCleanupError extends SessionRemoteError {}
class SessionFindError extends SessionRemoteError {}
class SessionTokenMismatchError extends Error {}




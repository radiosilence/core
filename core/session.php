<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/**
 * Session management.
 * @package auth
 * @subpackage core
 */

namespace Core;

import('core.dependency');
import('core.exceptions');

DEPENDENCY::require_classes('PDO');
DEPENDENCY::require_functions('setcookie','json_decode');

class Session
{
    /**
     * PDO instance
     */
    private $pdo;
    /**
     * Session sid
     */
    public $sid;
    /**
     * Session token
     */
    private $tok;
    /**
     * Session data
     */
    private $data = array();
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
     * Attach a PDO. NECESSARY!
     * @param \PDO $pdo
     */
    public function attach_pdo(\PDO $pdo) {
        $this->pdo = $pdo;
        SESSIONDBERROR::$pdo = $pdo;
        return $this;
    }
    public function set_remote_addr($addr) {
        $this->remote_addr = $addr;
        return $this;
    }

    public function read_cookie($cookie) {
        $this->cookie_sid = $cookie['sid'];
        $this->cookie_tok = $cookie['tok'];
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
            $this->find_session_in_db();
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
        if (isset($this->data[$prop_name])) {
            return $this->data[$prop_name];
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
        $this->data[$prop_name] = $prop_value;
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
     * Sets the object's session to the right things.
     */
    private function set_session() {
        $this->sid = $this->cookie_sid;
        $this->data = $this->found_session->data, true);
        $this->tok = $this->cookie_tok;
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

class SessionDBError extends Error {
    public static $pdo;
    public $error_message;
    public function __construct() {
        $error_info = self::$pdo->errorInfo();
        $this->error_message = $error_info[2];
        parent::__construct();
    }
}

class SessionUpdateError extends SessionDBError {}
class SessionInsertError extends SessionDBError {}
class SessionDeleteError extends SessionDBError {}
class SessionCleanupError extends SessionDBError {}
class SessionFindError extends SessionDBError {}
class TokenMismatchError extends Error {}


?>
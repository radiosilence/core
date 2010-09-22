<?php /* Copyright 2010 James Cleveland. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY James Cleveland "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL JAMES CLEVELAND OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the
authors and should not be interpreted as representing official policies, either expressed
or implied, of James Cleveland. */

/**
 * Session management.
 * @package auth
 * @subpackage core
 */

namespace Core;

import('core.dependency');

DEPENDENCY::require_classes('PDO');
DEPENDENCY::require_functions('setcookie');

class Session
{
    /**
     * PDO instance
     */
    private $pdo;
    /**
     * Session sid
     */
    private $session = 0;
    /**
     * Session token
     */
    private $tok = 0;
    /**
     * Session user id
     */
    public $user_id = 0;
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

	public function attach_pdo(\PDO $pdo) {
		$this->pdo = $pdo;
		return $this;
	}

	public function read_cookie($sid, $tok) {
		$this->cookie_sid = $sid;
		$this->cookie_tok = $tok;
		return $this;
	}
	
	public function attach_auth_config($file=False) {
		if(empty($file)){
			$file = SITE_PATH . "config/auth.php";
		}
		if(!file_exists($file)) {
			throw new FileNotFoundError($this->auth_file);	
		}
		require($file);
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
        
        $pdo = $this->pdo;
		$sid = $this->cookie_sid;
        $tok = $this->cookie_tok;

        if(!empty($sid) && !empty($tok)) {
            
            if(DEBUG) FB::log("Attempting to load supposed session [" . $sid . "] ...");

            # Is it the right tok for sid and IP?
            $sth = $pdo->prepare("
                SELECT sid, data, user_id
                FROM sessions
                WHERE sid = :sid
                AND tok = :tok
                AND ipv4 = :ipv4
                LIMIT 1
            ");
                
            $e = $sth->execute(array(
                ":sid" => $sid,
                ":tok" => $tok,
                ":ipv4" => $_SERVER["REMOTE_ADDR"]
            ));
                
            if($e) {
                $row = $sth->fetch();
                $chall = $this->create_token($sid);
                # would a recreation of this from this host be the same as the real thing?
                if($chall == $tok)
                {
                    if(DEBUG) FB::send("Challenge: ". $chall . " Real: " . $tok, "Toks");

                    $this->set_session(array(
                        "sid" => $sid,
                        "data" => json_decode($row["data"], true),
                        "tok" => $tok,
                        "user_id" => $row["user_id"])
                    );
                }

            } else {
                die($pdo->error);
            }
        }
        if($this->session) {
            if(DEBUG) FB::log($this, "✔ Loaded session [" . $this->session . "]");
        } else {
            if(DEBUG) FB::log("× Session not loaded because it doesn't exist.");
        }
		
		return $this;
	}

    public function __destruct() {

        if($this->session) {
            if(DEBUG) FB::log($this, "Saving session [" . $this->session . "] ... ");
            $pdo = $this->pdo;
            
            $sth = $pdo->prepare("
                UPDATE sessions
                SET data = :data
                WHERE sid = :sid
                LIMIT 1"
            );
            
            $sth->execute(array(
                ":data" => json_encode($this->data),
                ":sid" => $this->session
            ));

            if($e) {
                if(DEBUG) FB::log("✔ Saved session.");
            } else {
                if(DEBUG) FB::error("× Could not write session.");
            }

        } else {
            if(DEBUG) FB::log("× Not destroying session because it doesn't exist.");
        }

    }

    /**
     * Gets stuff from data, overloader.
     * @param $prop_name Property
     * @param $prop_value Property data
     * @return boolean
     */
    function __get($prop_name) {
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
    function __set($prop_name, $prop_value) {
        $this->data[$prop_name] = $prop_value;
        return true;
    }

    /**
     * Creates a session, puts it in the database,
     * returns the ID.. Assumes login has succeeded.
     * @param integer $user_id User ID
     * @param string $passhash Hashed password.
     * @param string $email User's email.
     * @return array Either a fail or an array with $sid, $id, and $tok.
     */
    public function create_session($user_id) {
        $sid = $this->create_sid();
        $tok = $this->create_token($sid);
        $pdo = $this->pdo;

        $sth = $pdo->prepare("
            DELETE
            FROM sessions
            WHERE user_id = :user_id
            AND ipv4 = :ipv4"
        );
        $sth->execute(array("user_id" => $user_id, "ipv4" => $_SERVER["REMOTE_ADDR"]));


        $sth2 = $pdo->prepare("
            INSERT INTO sessions (
                sid, tok, ipv4, user_id
           )
            VALUES (
                :sid, :tok, :ipv4, :user_id
           )
        ");
        
        $res = $sth2->execute(array(
            ":sid" => $sid,
            ":tok" => $tok,
            ":ipv4" => $_SERVER["HTTP_X_FORWARDED_FOR"],
            ":user_id" => $user_id 
        ));
        
        if($res) {
            $return = array("sid" => $sid, "id" => $user_id, "tok" => $tok);
        } else {
            $return = 0;
        }
        
        return $return;
    }

    /**
     * Destroys the session, deletes from DB, unsets cookies.
     * 
     */
    public function destroy_session() {
        $pdo = $this->pdo;
        $sth = $pdo->prepare("
            DELETE FROM sessions
            WHERE sid = :sid
            AND ipv4 = :ipv4
            AND tok = :tok
        ");
        
        $sth->execute(array(
            ":sid" => $this->session,
            ":ipv4" => $_SERVER["HTTP_X_FORWARDED_FOR"]
        ));
        
        setcookie("sid", "DEAD", time()-1, WWW_PATH . "/", null, false, true);
        setcookie("tok", "DEAD", time()-1, WWW_PATH . "/", null, false, true);
        return 1;
    }
         
    /**
     * Makes a hash from a password string.
     * @param string $password unhashed password
     * @return string password hash
     */
    public function password_hash($password, $salt) {
        $hash = hash("sha256", $password . sha1($salt . $this->base_salt));
        return $hash;
    }

    /**
     * Sets the object's session to the right things.
     * @param hash $sid
     * @param integer $id
     */
    public function set_session($s) {
        if(DEBUG) FB::send($s, "Setting Session");
        $this->session = $s["sid"];
        $this->user_id = $s["user_id"];
        $this->data = $s["data"];
        $this->tok = $s["tok"];
    }

    /**
     * Sets the cookies, with httponly.
     * @param hash $tok
     */
    public function set_cookie() {
        setcookie("sid", $this->session, time()+(3600*24*65), WWW_PATH . "/", null, false, true);
        setcookie("tok", $this->tok, time()+(3600*24*65), WWW_PATH . "/", null, false, true);
        if(DEBUG) FB::log("Setting up cookies.");
    }

    /**
     * Generates a new auth token based on session ID.
     * @param string $passhash Password hash.
     * @param string $email User's email.
     */
    private function create_token($sid) {
        # Token generation code.
        $hash = sha1($this->keyphrase . $_SERVER["HTTP_X_FORWARDED_FOR"] . $sid);
        return $hash;
    }

    /**
     * Generate a simple sid hash.
     * @return hash sid
     */
    private function create_sid() {
        return sha1(microtime() . $_SERVER["HTTP_X_FORWARDED_FOR"]);
    }
}
?>
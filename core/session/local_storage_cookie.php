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
import('core.dependency');

\Core\DEPENDENCY::require_functions('setcookie');

class LocalStorageCookie implements LocalStorage {
	private $sid;
	private $tok;
	
	public function __construct() {
        $this->cookie_sid = $_COOKIE['sid'];
        $this->cookie_tok = $_COOKIE['tok'];
	}
	
	public function __destruct() {
        setcookie("sid", $this->sid, time()+(3600*24*65), null, null, false, true);
        setcookie("tok", $this->tok, time()+(3600*24*65), null, null, false, true);
	}
	
	public function read() {
        return array(
        	'sid' => $this->sid,
        	'tok' => $this->tok
        );
    }
    /**
     * Sets the cookies, with httponly.
     */
    private function save($sid,$tok) {
    	$this->sid = $sid;
		$this->tok = $tok;
    }
	
    /**
     * Destroys sid and tok cookies.
     */
    private function destroy() {
    	$this->sid = null;
		$this->tok = null;
    }
}

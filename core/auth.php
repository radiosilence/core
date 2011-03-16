<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core;

import('3rdparty.phpass');
import('core.containment');
import('core.exceptions');

class Auth extends \Core\Contained {
    protected $_table;
    protected $_session;
    protected $_storage;

    public static function hash($data, $field=False) {
        $t_hasher = new \PasswordHash(8, FALSE);
        if($field) {
            if(empty($data[$field])) {
                throw new AuthEmptyPasswordError();
            }
            $data[$field] = $t_hasher->HashPassword($data[$field]);
            return $data;
        } else {
            if(empty($data)) {
                throw new AuthEmptyPasswordError();
            }
            return $t_hasher->HashPassword($data);
        }
    }

    
    public function __construct($parameters) {
        $this->_password_field = $parameters['password_field'] ?
            $parameters['password_field'] : 'password';
        $this->_user_field = $parameters['user_field'] ?
            $parameters['user_field'] : 'username';
    }
    public function set_table($table) {
        $this->_table = $table;
        return $this;
    }
    public function attach_session($session) {
        $this->_session = $session;
        return $this;
    }

    public function attach_storage($storage) {
        $this->_storage = $storage;
        return $this;
    }
    public function attempt($username, $password) {
        $result = $this->_storage->fetch(new \Core\Dict(array(
            'filters' => new \Core\Li(
                new \Core\Filter($this->_user_field, $username)
            ))));

        if(count($result) == 0){
            throw new InvalidUserError();
        }
        $t_hasher = new \PasswordHash(8, False);
        if(!$t_hasher->CheckPassword($password, $result[0][$this->_password_field])) {
            throw new IncorrectPasswordError();
        }
        $this->_set_session($result[0]['id'], $result[0]);
    }

    public function logout() {
        $this->_session->remove('auth');
    }
    protected function _set_session($id, $data) {
        $this->_session['auth'] = array(
            'id' => $id,
            'data' => $data
        );
    }

    public function user_data($data=False) {
        if($data) {
            $this->_session['auth'] = array(
                'id' => $this->user_id(),
                'data' => $data
            );
        } else {
            $this->_check_logged_in();
            return $this->_session['auth']['data'];            
        }
    }

    public function user_id() {
        $this->_check_logged_in();
        return $this->_session['auth']['id'];
    }

    protected function _check_logged_in() {
        if($this->_session['auth']['id'] <= 0) {
            throw new AuthNotLoggedInError();
        }
    }
}


class AuthNotLoggedInError extends \Core\StandardError {}
class AuthEmptyPasswordError extends \Core\StandardError {}

class AuthContainer extends \Core\Container {
    public function get_auth($table, \Core\Session\Handler $session, $parameters=False) {
        $storage = Storage::container()
            ->get_storage($table);

        return Auth::create($parameters)
            ->set_table($table)
            ->attach_session($session)
            ->attach_storage($storage);
    }
    
}

class InvalidUserError extends \Core\Error {
    public function __construct() {
        parent::__construct("Invalid user.");
    }
}

class IncorrectPasswordError extends \Core\Error {
    public function __construct() {
        parent::__construct("Incorrect password.");
    }
}
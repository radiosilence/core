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

import('core.hasher');
import('core.containment');
import('core.mapping');
import('core.exceptions');

class Auth extends \Core\Contained {
    protected $_table;
    protected $_session;
    protected $_roots = array();

    public static function hash($data, $field=False) {
        $hasher = new Hasher();
        if($field) {
            if(empty($data[$field])) {
                throw new AuthEmptyPasswordError();
            }
            $data[$field] = $hasher->hash($data[$field]);
            return $data;
        } else {
            if(empty($data)) {
                throw new AuthEmptyPasswordError();
            }
            return $hasher->hash($data);
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
        $result = \Core\Storage::container()
            ->get_storage($this->_table)
            ->fetch(array(
                'filter' => new \Core\Filter($this->_user_field, $username)
            ))->{0};

        if(!$result){
            throw new InvalidUserError();
        }
        try {
            $hasher = Hasher::create()
                ->check($password, $result[$this->_password_field]);            
            $this->_set_session($result['id'], $result);
        } catch(HashMismatch $e) {
            throw new IncorrectPasswordError();
        }
    }

    public function logout() {
        $this->_session->remove('auth');
    }

    public function update_user_data() {
        $this->_set_session($this->user_id());
        return $this;
    }

    protected function _set_session($id, $data=False) {
        if(!$data) {
            $data = \Core\Storage::container()
                ->get_storage($this->_table)
                ->fetch(array(
                    'filter' => new \Core\Filter('id', $id)
                ))->{0};
        }
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
            return new \Core\Dict($this->_session['auth']['data']); 
        }
    }

    public function is_root($user_id) {
        if(!$user_id) {
            $user_id = $this->user_id();
        }
        if(in_array($user_id, $this->_roots)) {
            return True;
        } else {
            $root = Admin::container()
                ->is_root($user_id, $this->_table);
            if($root) {
                $this->_roots[] = $user_id;
                return True;
            }
        }
        return False;
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

    public function check_admin($type, $entity, $user_id=False) {
        if(!$user_id) {
            $user_id = $this->user_id();
        }
        if(!$this->is_root($user_id)) {
            $admin = Admin::container()
                ->get_by_role($user_id, $entity, $type, $this->_table);
            if(!$admin) {
                throw new AuthDeniedError();
            }        
        }
    }

    public function add_admin($type, $entity, $user_id=False) {
        if(!$user_id) {
            $user_id = $this->user_id();
        }
        $admin = Admin::mapper()->create_object(array(
            'type' => $type,
            'entity' => $entity,
            $this->_table => $user_id
        ), $this->_table);
        \Core\Storage::container()
            ->get_storage('Admin')
            ->save($admin);
    }

    public function get_administrated_ids($type, $user_id=False) {
        if(!$user_id) {
            $user_id = $this->user_id();
        }
        $params = array('type'=>$type);
        $entities = array();
        if(!$this->is_root($user_id)) {
            $params['user_id'] = $user_id;
        }
        $admins = Admin::container()
            ->list_privileges($params, $this->_table);
        foreach($admins as $admin) {
            $entities[] = $admin['entity'];
        }
        return $entities;
    }
}

class AuthError extends \Core\StandardError {}
class AuthNotLoggedInError extends AuthError {}
class AuthEmptyPasswordError extends AuthError {}
class AuthDeniedError extends AuthError {}


class AuthAttemptError extends AuthError {}
class InvalidUserError extends AuthAttemptError {}
class IncorrectPasswordError extends AuthAttemptError {}

class AuthContainer extends \Core\Container {
    public function get_auth($table, \Core\Session\Handler $session, $parameters=False) {

        return Auth::create($parameters)
            ->set_table($table)
            ->attach_session($session);
    }
    
}

class Admin extends \Core\Mapped {
    public static $fields = array("entity", "type");
    public function set_user_field($user_field) {
        array_unshift(static::$fields, $user_field);
        return $this;
    }
}

class AdminMapper extends \Core\Mapper {
    public function create_object($data, $user_field='user') {
        $admin = Admin::create($data)
            ->set_user_field($user_field);
        return $admin;
    }
}

class AdminContainer extends \Core\MappedContainer {
    public function get_by_role($user_id, $entity, $type, $user_field='user') {
        $non_roots = \Core\Storage::container()
            ->get_storage('Admin')
            ->fetch(array(
                'filters' => array(
                    new \Core\Filter($user_field, $user_id),
                    new \Core\Filter('entity', $entity),
                    new \Core\Filter('type', $type)
                )
            ));
        if(count($non_roots) > 0) {
            return Admin::mapper()->create_object($non_roots[0])
                ->set_user_field($user_field);
        }


        return False;
    }

    public function list_privileges($parameters, $user_field='user') {
        $list = new \Core\Li();
        $filters = array();
        if($parameters['type']) {
            $filters[] = new \Core\Filter('type', $parameters['type']);
        }
        if($parameters['user_id']) {
            $filters[] = new \Core\Filter($user_field, $parameters['user_id']);
        }
        $results = \Core\Storage::container()
            ->get_storage('Admin')
            ->fetch(array(
                'filters' => $filters
            ));
        foreach($results as $result) {
            $list->append(Admin::mapper()
                ->create_object($result, $user_field));
        }

        return $list;
    }

    public function is_root($user_id, $user_field='user') {
         $roots = \Core\Storage::container()
            ->get_storage('Admin')
            ->fetch(array(
                'filters' => array(
                    new \Core\Filter($user_field, $user_id),
                    new \Core\Filter('type', 'ROOT')
                )
            ));
        if(count($roots) > 0) {
            return True;
        }
        return False;
    }
}
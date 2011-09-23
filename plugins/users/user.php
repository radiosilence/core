<?php

namespace Plugins\Users;

import('core.mapping');
class User extends \Core\Mapped {
    public static $fields = array("username", "password", "avatar");

    public static function validation() {
        return array(
            'username' => array(
                array(
                    'type' => 'unique',
                    'mapper' => 'user'
                ),
                'default'
            ),
            'password' => array(
                array(
                    'type' => 'password',
                    '2ndfield' => 'password_confirm',
                )
            ),
        );
    }
}

class UserMapper extends \Core\Mapper {
    public function create_object($data) {
        $data['avatar'] = $data['avatar'] ? $data['avatar'] : 'default.png';
        return User::create($data, True);
    }
}

class UserContainer extends \Core\MappedContainer {}
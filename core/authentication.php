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

class Authenticator extends \Core\Contained {

    public static function create_hash($password, $salt,) {
        
    }

    public static function create_salt() {
        
    }

    public function set_secondary($table) {
        $this->secondary_table = $table;
    }
}

class AuthenticatorContainer extends \Core\ConfiguredContainer {

    public function get_standard_authorisation() {
        import('core.authentication');

        $authenticator = new Authenticator();
        $arp = new RemoteStorage\PDO();
        
        $srp->attach_pdo($this->parameters['pdo']);
        $this->load_config();

        $authenticator->attach_remote_storage($arp)
            ->attach_crypto_config($this->config['crypto']);
    }
    
}
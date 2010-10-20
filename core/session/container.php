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

import('core.exceptions');
import('core.utils.ipv4');
import('core.container');

class Container extends \Core\Container {

    public function get_standard_session() {

        import('core.session.handler');
        import('core.session.remote_storage.pdo');
        import('core.session.local_storage.cookie');

        $sh = new Handler();
        $srp = new RemoteStorage\PDO();
        $slc = new LocalStorage\Cookie();

        $this->test_valid_parameter('pdo', '\PDO' );

        $srp->attach_pdo($this->parameters['pdo']);
        if(!isset($this->parameters['crypto_config'])) {
            $this->parameters['crypto_config'] = CONFIG_PATH . 'crypto.php';
        }

        try{
            $sh->attach_remote_storage($srp)
                ->attach_local_storage($slc)
                ->attach_crypto_config($this->parameters['crypto_config'])
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

?>

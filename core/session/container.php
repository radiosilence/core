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

class Container {
    private $parameters = array();
    public function __construct($parameters) {
        $this->parameters = $parameters;
    }

    public function get_standard_session() {

        import('core.session.handler');
        import('core.session.remote_storage.pdo');
        import('core.session.local_storage.cookie');

        $sh = new \Core\Session\Handler();
        $srp = new \Core\Session\RemoteStorage\PDO();
        $slc = new \Core\Session\LocalStorage\Cookie();

        $srp->attach_pdo($this->parameters['pdo']);

        try{
            $sh->attach_remote_storage($srp)
                ->attach_local_storage($slc)
                ->attach_crypto_config()
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
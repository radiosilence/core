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
 * Hail Satan.
 *
 * @package core
 * @subpackage core
 * @abstract Extended by the actual controllers
 */

namespace Core;

import('core.dependency');

abstract class Controller {

    public function load_locale($file) {
        include SITE_PATH . DIRSEP . "languages" . DIRSEP . LOCALE . DIRSEP . $file . ".php";
    }

    /**
     * All controllers need to have a default option.
     * @param string $args the arguments got from the URL
     */
    abstract function index($args);
}
?>

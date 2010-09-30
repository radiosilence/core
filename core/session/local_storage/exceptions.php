<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core\Session\LocalStorage;

import('core.exceptions');

class Error extends \Core\Error {
    public function __construct($message) {
        parent::__construct(sprintf("Session error [Local]: %s", $message));
    }
}

?>
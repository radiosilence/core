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
 * Essentially template handling.
 *
 * @package core
 * @subpackage core
 */

namespace Core;

import('core.types');

class View extends Arr {
    public function show($name) {
        $path = SITE_PATH . '/views/' . $name . '.php';

        if(file_exists($path) == false) {
            throw new TemplateNotFoundError($path);
            return false;
        }
        // Load variables
        foreach($this->data as $key => $value) {
            $$key = $value;
        }
        include($path);
    }
}

class TemplateNotFoundError extends Error {
    public function __construct($path){
        # We could do something other than trigger an error here, like display a default template or something.
        trigger_error(sprintf('Template "%s" cannot be found.', $path), E_USER_ERROR);
    }
}
?>

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

class Template extends CoreDict {
    protected $parent;
    protected $path;
    protected $data = array();
    protected $sections = array();
    protected $current_section = null;
    public function render($name) {
        extract($this->data);
        $this->path = sprintf("%s/templates/%s", SITE_PATH, $name);
        if(file_exists($this->path) == false) {
            throw new TemplateNotFoundError($path);
        }
        ob_start();
        require($this->path);
        return ob_get_clean();
    }   
}

class TemplateNotFoundError extends Error {
    public function __construct($path){
        # We could do something other than trigger an error here, like display a default template or something.
        trigger_error(sprintf('Template "%s" cannot be found.', $path), E_USER_ERROR);
    }
}
?>

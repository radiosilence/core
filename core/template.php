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
import('core.security.antixsrf');
class Template extends Dict {
    protected $_parent;
    protected $_path;
    protected $_sections = array();
    protected $_current_section = null;
    protected $_utils = array();
    protected $_file;
    protected $_absolute = False;

    public function attach_util($name, $util) {
        $this->_utils[$name] = $util;
        return $this;
    }

    public function set_file($file) {
        $this->_file = $file;
    }

    public function render($name=False) {
        if(!$name) {
            $name = $this->_file;
        }
        $valid_path = False;
        if(file_exists($name)) {
            $valid_path = $name;
        } else {
            $paths = array(SITE_PATH, CORE_PATH);
            foreach($paths as $path) {
                $t = sprintf("%s/templates/%s", $path, $name);
                if(file_exists($t)) {
                    $valid_path = $t;
                    break; 
                }
            }            
        }
        if(!$valid_path) {
            throw new TemplateNotFoundError($name);
        }

        ob_start();
        extract($this->__data__);
        require($valid_path);
        return ob_get_clean();
    }   
}

class TemplateNotFoundError extends Error {
    public function __construct($path){
        # We could do something other than trigger an error here, like display a default template or something.
        trigger_error(sprintf('Template "%s" cannot be found.', $path), E_USER_ERROR);
    }
}
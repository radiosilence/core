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

    public function url_for($url) {
        if(!isset($this->_utils['antixsrf'])) {
            throw new \Core\Error('AntiXSRF module not loaded.');
        }
        var_dump($this);
        return $url . '/xsrf:' . $this->_utils['antixsrf']->get_hash();
    }

    public function attach_util($name, $util) {
        $this->_utils[$name] = $util;
        return $this;
    }

    public function render($name) {
        if($this->_utils['antixsrf'] instanceof \Core\Security\AntiXSRF) {
            $this->__xsrf__ = '/xsrf:' . $this->_utils['antixsrf']->get_hash();
        }
        extract($this->__data__);
        $this->_path = sprintf("%s/templates/%s", SITE_PATH, $name);
        if(file_exists($this->_path) == false) {
            throw new TemplateNotFoundError($path);
        }
        ob_start();
        require($this->_path);
        return ob_get_clean();
    }   
}

class TemplateNotFoundError extends Error {
    public function __construct($path){
        # We could do something other than trigger an error here, like display a default template or something.
        trigger_error(sprintf('Template "%s" cannot be found.', $path), E_USER_ERROR);
    }
}
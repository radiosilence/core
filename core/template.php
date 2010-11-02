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

class Template extends Arr {
    protected $parent;
    protected $path;
    protected $sections = array();
    protected $current_section = null;

    public function __construct($name) {
        $this->path = sprintf("%s/templates/%s", SITE_PATH, $name);

        if(file_exists($this->path) == false) {
            throw new TemplateNotFoundError($path);
        }
        
    }
 
    public function begin($section_name) {
        ob_start();
        $this->current_section = $section_name;
    }

    public function end() {
        if (!is_null($this->parent)) {
            $this->sections[$this->current_section] = ob_get_clean();
        } else {
            if (isset($this->sections[$this->current_section])) {
                ob_end_clean();
                echo $this->sections[$this->current_section];
            } else {
                echo ob_get_clean();
            }
        }
    }

    public function set_sections($sections) { 
        $this->sections = $sections;
    }

    public function extend($name) {
        $this->parent = new Template($name);
    }
 
    public function build() {
        ob_start();
        extract($this->data);
        include $this->path;
        $output = ob_get_clean();
     
        if (!is_null($this->parent)) {
            $this->parent->set_sections($this->sections);
            return $this->parent->display();
        } else {
            echo "<<<<<<<<<<<<<<<<$output>>>>>>>>>>>>";
        }
     
    }
 
    public function display() {
        echo $this->build();
    }
}

class TemplateNotFoundError extends Error {
    public function __construct($path){
        # We could do something other than trigger an error here, like display a default template or something.
        trigger_error(sprintf('Template "%s" cannot be found.', $path), E_USER_ERROR);
    }
}
?>

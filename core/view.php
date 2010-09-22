<?php
/**
 * Essentially template handling.
 *
 * @package core
 * @subpackage core
 */

namespace Core;

class View {
    private $vars = array();
    
    public function set($varname, $value) {
        $this->vars[$varname] = $value;
        return true;
    }

    public function remove($varname) {
        unset($this->vars[$varname]);
        return true;
    }

    public function show($name) {
        $path = SITE_PATH . 'views' . DIRSEP . $name . '.php';

        if(file_exists($path) == false) {
            throw new TemplateNotFoundError($path);
            return false;
        }
        // Load variables
        foreach($this->vars as $key => $value) {
            $$key = $value;
        }
        include ($path);
    }
}

class TemplateNotFoundError extends \Exception {
    public function __construct($path){
        # We could do something other than trigger an error here, like display a default template or something.
        trigger_error(sprintf('Template "%s" cannot be found.', $path), E_USER_ERROR);
    }
}
?>
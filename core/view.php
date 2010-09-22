<?php
/**
 * Essentially template handling.
 *
 * @package core
 * @subpackage core
 */
class View
{
    private $registry;
    private $vars = array();
    
    public function set($varname, $value, $overwrite = false) {
        if(isset($this->vars[$varname]) == true and $overwrite == false) {
            throw new Exception('Unable to set var "' . $varname . '". Already set, and overwrite not allowed.');
            return false;
        }
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
            trigger_error('Template `' . $path . '` does not exist.', E_USER_NOTICE);
            return false;
        }
        // Load variables
        foreach($this->vars as $key => $value) {
            $$key = $value;
        }
        include ($path);
    }
}
?>
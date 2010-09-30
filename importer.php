<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

class ImportError extends \Exception {
    public function __construct($module_name) {
        parent::__construct(sprintf('Module "%s" was not found in any of available paths.', $module_name));    
    }
}


class Importer {
    private static $importer;
    public $include_paths = array();

    private $module_dir_parts = array();
    private $module_full_parts = array();
    private $module_last_part;

    public static function instantiate() {
        self::$importer = new Importer();
        self::$importer->set_include_paths();
    }

    public static function import_module($module_name) {
        self::$importer->populate_properties($module_name);
        if(!self::$importer->try_paths()) {
            throw new ImportError($module_name);
        }
    }

    public static function add_include_path($path) {
        self::$importer->include_paths[] = $path;
    }

    public function set_include_paths($include_paths=False) {
        if(!$include_paths) {
            $include_paths = array_merge(array(__DIR__), explode(PATH_SEPARATOR, ini_get('include_path')));
        }
        $this->include_paths = $include_paths;
    }

    private function try_paths() {
        foreach($this->include_paths as $include_path) {
            if($this->try_path($include_path)){
               return True;
            }
        }
        return False;
    }
    private function populate_properties($module_name){
        $this->module_parts = explode('.', $module_name);
        $this->module_directory_parts = $this->module_parts;
        array_pop($this->module_directory_parts);
    }

    private function try_path($include_path) {
        if(end($this->module_parts) == '*') {
            return $this->include_group($include_path . '/' . implode('/', $this->module_directory_parts));
        } else {
            return $this->include_module($include_path . '/' . implode('/', $this->module_parts) . '.php');
        }
    }

    private function include_group($directory) {
        if(!is_dir($directory)) {
            return False;
        } else {
            foreach(glob($directory . '*.php') as $path) {
                require_once($path);
            }
            return True;
        }
    }

    private function include_module($path) {
        if(file_exists($path)) {
            require_once($path);
            return True;
        } else {
            return False;
        } 
    }
}
?>
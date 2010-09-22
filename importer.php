<?php
/* Copyright 2010 James Cleveland. All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are
permitted provided that the following conditions are met:

   1. Redistributions of source code must retain the above copyright notice, this list of
      conditions and the following disclaimer.

   2. Redistributions in binary form must reproduce the above copyright notice, this list
      of conditions and the following disclaimer in the documentation and/or other materials
      provided with the distribution.

THIS SOFTWARE IS PROVIDED BY JAMES CLEVELAND "AS IS" AND ANY EXPRESS OR IMPLIED
WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND
FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL JAMES CLEVELAND OR
CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON
ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

The views and conclusions contained in the software and documentation are those of the
authors and should not be interpreted as representing official policies, either expressed
or implied, of James Cleveland. */

class ImportError extends \Exception {
    public function __construct($class) {
        trigger_error(sprintf('Class "%s" was not found in any of available paths.', $class), E_USER_ERROR);    
    }
}


class Importer {
    private $imported_files = array();
    private $include_paths = array();

    private $module_dir_parts = array();
    private $module_full_parts = array();
    private $last_part;

    public function __construct() {}

    public function import_module($module_name) {
        $this->populate_properties($module_name);
        return $this->try_paths();
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
        $this->include_paths = array_merge(array(__DIR__), explode(':', ini_get('include_path')));
        $this->module_parts = explode('.', $module_name);
        $this->module_directory_parts = $this->module_parts;
        $this->last_part = array_pop($this->module_dir_parts);
    }

    private function try_path($include_path) {
        if($this->last_part == '*') {
            return $this->include_group($include_path . DIRSEP . implode(DIRSEP, $this->module_directory_parts));
        } else {
            return $this->include_module($include_path . DIRSEP . implode(DIRSEP, $this->module_parts) . '.php');
        }
    }

    private function include_group($directory) {
        if(!is_dir($directory)) {
            return False;
        } else {
            foreach(glob($directory . '*.php') as $path) {
                include_once($path);
            }
            return True;
        }
    }

    private function include_module($path) {
        if(file_exists($path)) {
            include_once($path);
            return True;
        } else {
            return False;
        } 
    }
}
?>
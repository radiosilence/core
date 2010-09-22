<?php
/**
 * Depency testing.
 */
namespace Core;

class DependencyError extends \Exception {
    public function __construct($name, $type, $file) {
        if(DEBUG) debug_print_backtrace();
        trigger_error(sprintf("File '%s' requires %s '%s', which is not available.", $file, $type, $name), E_USER_ERROR);
    }
}

class Dependency {
    public static function require_functions($functions) {
        self::test($functions,'function');
    }

    public static function require_classes($classes) {
        self::test($classes,'class');
    }

    private function test($inputs,$type){
        if(!is_array($inputs)){
            $inputs = array($inputs);
        }
        foreach($inputs as $input) {
            $f = $type . '_exists';
            if(!$f($input)){
                $backtrace = debug_backtrace();
                throw new DependencyError($input, $type, $backtrace[1]['file']);
            }
        }
        
    }
}
?>
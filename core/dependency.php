<?php
/**
 * Depency testing.
 */
namespace Core;

class DependencyError extends \Exception {
    public function __construct($function, $file) {
        trigger_error(sprintf("File '%s' requires function '%s()', which is not available.", $file, $function), E_USER_ERROR);
    }
}

class Dependency {
    public static function require_functions($functions) {
        foreach($functions as $function) {
            if(!function_exists($function)){
                $backtrace = debug_backtrace();
                throw new DependencyError($function, $backtrace[0]['file']);
            }
        }
    }
}
?>
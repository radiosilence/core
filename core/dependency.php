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
    public static function require_functions() {
        self::test(func_get_args(),'function');
    }

    public static function require_classes() {
        self::test(func_get_args(),'class');
    }

    private static function test(Array $inputs, $type){
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
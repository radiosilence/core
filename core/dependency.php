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

    private function test(Array $inputs, $type){
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
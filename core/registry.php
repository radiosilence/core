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
 * This is like a store for objects.
 *
 * @package core
 * @subpackage core
 */

namespace Core;

class Registry {
    private static $vars = array();

    public static function set($key, $var) {
        if(isset(self::$vars[$key]) == true) {
            throw new Exception('Unable to set var "' . $key . '". Already set.');
        }
        self::$vars[$key] = $var;
        return true;
    }

    public static function get($key) {
        if(isset(self::$vars[$key]) == false) {
            return false;
        }
        return self::$vars[$key];
    }

    public static function remove($var) {
        unset(self::$vars[$key]);
    }

    public static function spill() {
        echo "<pre>";
        print_r(self::$vars);
        echo "</pre>";
    }
}
?>
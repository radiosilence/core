<?php
/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 *
 */
 
namespace Core\Utils;

class Env {
    public static function site_name() {
        return array_pop(explode('/',realpath(SITE_PATH)));
    }
    public static function using_ssl() {
        return $_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 'https' ? True : False;
    }
}
<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core;

import('core.template');
abstract class Plugin {
    abstract public static function plugin_name();
    public static function get_template($name) {
        $t = \Core\Template::create();
        $t->_jsapps = array();
        $t->set_file(
            sprintf("%s/plugins/%s/templates/%s",
                CORE_PATH,
                static::plugin_name(),
                $name),
            True);
        return $t;
    }
}
<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


namespace Core\Session;

abstract class LocalStorage {
    abstract public function get();
    abstract public function set($actual);
    abstract public function destroy();
}

abstract class RemoteStorage {
    abstract public function __set($prop_name, $prop_value);
    abstract public function __get($prop_name);
    abstract public function save();
    abstract public function add($actual);
    abstract public function load($untrusted);
    abstract public function destroy();
}


?>
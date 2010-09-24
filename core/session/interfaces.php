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

interface RemoteStorage {
    public function __set($prop_name, $prop_value);
    public function __get($prop_name);
    public function save();
    public function add($actual);
    public function load($untrusted);
    public function destroy();
} 

interface LocalStorage {
    public function get();
    public function set($actual);
    public function destroy();
}

?>
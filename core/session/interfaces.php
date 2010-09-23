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
	public function __set();
	public function __get();
	public function update($sid,$tok,$data,$remote_addr);
	public function add();
	public function find();
	public function destroy();
} 

interface LocalStorage {
	
}

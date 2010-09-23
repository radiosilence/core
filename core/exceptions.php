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

class Error extends \Exception {}

class HTTPError extends Error {
    public $error_codes = array(
        404 => "HTTP/1.0 404 Not Found"
    );
    public function __construct($code,$url=False){
        if(!isset($this->error_codes[$code])){
            throw new Exception("HTTP error with unknown error code.");
        }
        header($error_codes[$code]);
        printf("Encountered error %s whilst trying to serve page: %s", $this->error_codes[$code], !empty($url) ? $url : 'unknown');
        die();
    }
}

class FileNotFoundError extends Error {
	public function __construct($filename) {
		trigger_error(sprintf('Required file "%s" was not found.', $filename), E_USER_ERROR);
	}
}
?>
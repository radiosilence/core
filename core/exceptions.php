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

/**
 * TODO: Put this in a definitions file somwhere.
 */
class Error extends \Exception {
    protected $message;

    public function __construct($message) {
        $this->message = $message;
        parent::__construct($message);
    }

    public function show_error() {       
        $sapi_type = php_sapi_name();
        if (substr($sapi_type, 0, 3) != 'cli') {
            echo "<h1>";
            $text = "</h1><p>Unhandled core exception (this is very bad).</p><h2>Why?</h2><p>%s</p><h2>How?</h2><p><pre>%s</pre></p>This error was generated";
        } else {
            $text = "\n============\nUnhandled core exception (this is very bad).\n\nWhy?\n====\n%s\n\nHow?\n====\n%s\n\nThis error was generated";
        }

        trigger_error(sprintf($text, $this->message,
            $this), E_USER_ERROR);        
    }
}

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
		parent::__construct(sprintf('Required file "%s" was not found.', $filename));
	}
}
?>
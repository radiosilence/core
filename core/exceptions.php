<?php

namespace Core;

class HTTPError extends \Exception {
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
?>
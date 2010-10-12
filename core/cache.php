<?php

namespace Core;

class Cache {
    public function __construct($route=False) {
        if(file_exists($this->get_path()) && !DEBUG) {
            die(file_get_contents($this->get_path()));
        }
        else {
            ob_start();
            $this->dynamic = True;
        }
    }

    public function __destruct() {
        if($this->dynamic) {
            $handle = fopen($this->get_path(), 'w');
            fwrite($handle, ob_get_contents());
            fclose($handle);
        }
    }

    private function get_path() {
        if(!is_dir(CACHE_PATH)) {
            throw new CacheError('Cache dir does not exist.');
        }
        return CACHE_PATH . '/' . md5($route);
    }
}

class CacheError extends Error {
    
}
?>

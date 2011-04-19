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

class Status {
    const Success = "Success";
    const Fail = "Failure";

    protected $_type;
    protected $_message;
    protected $_errors = array();
    
    public function success($message) {
        $this->_type = Status::Success;
        $this->_message = $message;
        return $this->_r();
    }

    public function fail($message) {
        $this->_type = Status::Fail;
        $this->_message = $message;
        return $this->_r();
    }

    public function set_errors($errors) {
        $this->_errors = $errors;
    }

    protected function _r() {
        if($this->_async) {
            exit(json_encode(array(
                'status'=> $this->_type,
                'message' => $this->_message,
                'errors' => $errors
            )));
        } else {
            $t = $this->_template;
            $t->status = $this->_type;
            $t->message = $this->_message;
            $t->errors = $this->_errors;
            return $t->render();
        }   
    }
}
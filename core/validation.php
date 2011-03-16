<?php

/*
 * This file is part of core.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Core;

import('core.storage');

class Validator {
    protected $errors = array();
    protected $id;
    protected $_type;
    protected $_data;

    public static function validator($type) {
        $v = new Validator();
        $v->set_type($type);
        return $v;
    }

    public function set_type($type) {
        $this->_type = $type;
        return $this;
    }
    public function set_id($id) {
        $this->id = $id;
        return $this;
    }

    public function validate($data, $validation) {
        $this->_data = $data;
        foreach($validation as $k => $v) {
                $this->validate_field($k, $v);
        }
        if(count($this->errors) > 0) {
            throw new ValidationError($this->errors);
        }
        return $this;
    }

    protected function validate_field($field, $validation) {
        try {
            if(is_array($validation) && isset($validation['type'])) {
                $f = 'test_' . $validation['type'];
                $this->$f($this->_data[$field], $field, $validation);
            } else if(is_array($validation)) {
                foreach($validation as $v) {
                    $this->validate_field($field, $v);
                }
            } else {
                $f = 'test_' . $validation; 
                $this->$f($this->_data[$field]);                
            }
        } catch(InvalidError $e) {
            $this->errors[] = $this->get_message($field, $validation, $e->msg, $e->show_field);
        }
    } 

    public function get_errors() {
        return $this->errors();
    }

    protected function is_complex($array) {
        
    }

    protected function get_message($field, $validation, $msg, $show_field) {
        $field = (strlen($validation['title']) > 1 ? $validation['title'] : ucfirst($field));
        if(!empty($msg) && $show_field) {
            return sprintf("<strong>%s</strong> %s",
                $field,
                $msg
            );
        } else if(!empty($msg) && !$show_field) {
            return $msg;
        }
        else if($validation == 'default' || $validation['type'] == 'default') {
            return sprintf("<strong>%s</strong> must be set.",
                $field);
        } else if(is_array($validation)) {
            return sprintf("<strong>%s</strong> must be %s",
                $field,
                $validation['type']);
        } else {
            return sprintf("<strong>%s</strong> must be valid %s.",
                $field,
                ucfirst($validation)
            );
        }
    }
    protected function test_default($string) {
        if(strlen($string) < 1) {
            throw new InvalidError();
        }
    }
    protected function test_number($string) {
        if(!filter_var($string, FILTER_VALIDATE_INT)) {
            throw new InvalidError();
        }
    }
    protected function test_email($string, $field, $parameters) {
        if(!filter_var($string, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidError('must be valid e-mail address.');
        } 
    }
    protected function test_password($string, $field, $parameters) {
        $array = func_get_args();
        if(empty($string)) {
            return 0;
        }
        if($string != $this->_data[$parameters["2ndfield"]]) {
            throw new InvalidError("Passwords must match.", False);
        }
    }
    protected function test_unique($string, $field, $parameters) {
        $type = $this->_type;
        $match = $type::container()
            ->get_by_field($field, $string);
        if($match && $match['id'] != $this->id) {            
            throw new InvalidError();
        }
    }
    protected function test_foreign($string, $field, $parameters) {
        $type = $parameters['class'];
        $match = $type::container()
            ->get_by_field('id', $string);
        if(!$match) {        
            throw new InvalidError('must be selected.');
        }
    }
}

class ValidationError extends Error {
    protected $errors;
    public function __construct($errors) {
        $this->errors = $errors;
        parent::__construct(sprintf("Validation failed with %d error(s).",
            count($errors)));
    }
    public function get_errors() {
        return $this->errors;
    }
}

class InvalidError extends Error {
    public $msg;
    public $show_field;
    public function __construct($msg=False, $show_field=True) {
        $this->msg = $msg;
        $this->show_field = $show_field;
        parent::__construct('Invalid data.');
    }
}

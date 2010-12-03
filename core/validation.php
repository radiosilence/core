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

class Validator {
    protected $errors = array();
    protected $mappers = array();
    protected $id;

    public static function validator() {
        return new Validator();
    }

    public function attach_mapper($type, $mapper) {
        $this->mappers[$type] = $mapper;
        return $this;
    }

    public function set_id($id) {
        $this->id = $id;
    }

    public function validate($data, $validation) {
        $this->data = $data;
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
                $f = 'test_complex_' . $validation['type'];
                $this->$f($this->data[$field], $field, $validation);
            } else if(is_array($validation)) {
                foreach($validation as $v) {
                    $this->validate_field($field, $v);
                }
            } else {
                $f = 'test_valid_' . $validation; 
                $this->$f($this->data[$field]);                
            }
        } catch(InvalidError $e) {
            $this->errors[] = $this->get_message($field, $validation);
        }
    } 

    public function get_errors() {
        return $this->errors();
    }

    protected function is_complex($array) {
        
    }

    protected function get_message($field, $validation) {
        if($validation == 'default') {
            return sprintf("%s must be set.",
                ucfirst($field));
        } else if(is_array($validation)) {
            return sprintf("%s must be %s",
                ucfirst($field),
                $validation['type']);
        } else {
            return sprintf("%s must be valid %s.",
                ucfirst($field),
                ucfirst($validation)
            );
        }
    }
    protected function test_valid_default($string) {
        if(strlen($string) < 1) {
            throw new InvalidError();
        }
    }
    protected function test_valid_number($string) {
        if(!filter_var($string, FILTER_VALIDATE_INT)) {
            throw new InvalidError();
        }
    }
    protected function test_valid_email($string) {
        if(!filter_var($string, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidError();
        } 
    }
    protected function test_complex_unique($string, $field, $parameters) {
        $mapper = $this->mappers[$parameters['mapper']];
        if($object = $mapper->find_by($field, $string)) {
            if($object->id != $this->id) {
                throw new InvalidError();            
            }
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
    public function __construct() {
        parent::__construct('Invalid data.');
    }
}
<?php

/*
 * This file is part of the core framework.
 *
 * (c) James Cleveland <jamescleveland@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */


/**
 * Hail Satan.
 *
 * @package core
 * @subpackage core
 * @abstract Extended by the actual controllers
 */

namespace Core;

import('core.dependency');
import('core.types');
import('core.utils.throttle');
import('core.auth');

abstract class Controller extends \Core\Dict {
    protected $_args;
    protected $_async;

    public function __construct($args=False) {
        $this->_args = $args;
        $this->_async = ($_POST['_async'] == 'true');
    }
    public function load_locale($file) {
        include SITE_PATH . "/languages/" . LOCALE . '/' . $file . ".php";
    }
    /**
     * All controllers need to have a default option.
     * @param string $args the arguments got from the URL
     */
    abstract function index();
   

    protected function _return_message($status, $message, $errors=array(), $t=False) {
        if($this->_async) {
            echo json_encode(array(
                'status'=> $status,
                'message' => $message,
                'errors' => $errors
            ));
            exit();
        } else {
            if(!$t) {
                $t = $this->_template;
            }
            $t->_status = $status;
            $t->_message = $message;
            $t->_errors = $errors;
            return $t->render();
        }
    }

    protected function _unhandled_exception($e) {
        trigger_error($e->getMessage(), E_USER_WARNING);
        $this->_return_message("Error",
            "Unhandled exception.");
    }

    protected function _throttle($args=False) {
        if(!$args) {
            $args = array(
                \Core\Utils\Throttle::Second => 10,
                \Core\Utils\Throttle::Minute => 120
            );
        }
        try {
            $tid = $this->_auth->user_id();
        } catch(\Core\AuthNotLoggedInError $e) {
            $tid = False;
        }
        try {
            $throttle = new \Core\Utils\Throttle($args, $tid);
        } catch(\Core\Utils\TooManyReqsError $e) {
            $this->_template->set_file('message.php');
            $this->_template->content = $this->_return_message('Fail',
                'Too many requests. Please wait and try again soon.');
            $this->_template->render('main.php');
            throw new \Core\HTTPError(403);
        }
    }

}
?>

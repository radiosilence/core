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
 * Gets what we're going to do from the
 * http request.
 *
 * @package core
 * @subpackage core
 */

namespace Core;

import('core.controller');
import('core.exceptions');
import('core.containment');
import('core.types');

class Router extends Contained {
    private $path;
    private $routes = array();

    public function __construct($routes_file=null) {
        if(empty($routes_file)) {
            $routes_file = SITE_PATH . '/routes.php';
        }
        if(!file_exists($routes_file)) {
            throw new FileNotFoundError($routes_file);
        }
        require $routes_file;

        foreach($routes as $request => $destination) {
            $this->add_route($request, $destination);
        }
    }
    public function add_route($request, $destination) {
        $this->routes[$request] = new Route($destination);
    }

    public function route($request) {
        $route = $this->find_route($request);
        import('controllers.' . strtolower($route->class));
        $class = sprintf('\Controllers\%s', $route->class);
        $controller = new $class($route->parameters);
        $method = $route->parameters['method'];
        if(!method_exists($controller,$method)) {
            $method = 'index';
        }
        $controller->$method();
    }

    private function find_route($request) {
        foreach($this->routes as $potential => $route) {
            if(preg_match('/' . $potential . '/', $request, $matches)) {
                return $route->assign_vars($matches);
            }
        }
        throw new HTTPError(404, $request);
    }
}

class Route {
    public $class;
    public $method;
    public $parameters = array();

    public function __construct($destination) {
        $this->parse_destination($destination);
    }

    public function parse_destination($destination) {
        $route = array();
        $destination = explode(':', $destination);
        $this->class = $destination[0];
        $destination[1] = explode(';', $destination[1]);
        
        foreach($destination[1] as $value) {
            $value = explode('=', $value);
            $this->parameters[$value[0]] = $value[1];
        }
    }

    public function assign_vars($vars) {
        $patterns = array();
        $replacements = array();
        foreach($vars as $key => $value) {
            $patterns[$key] = '/\$' . $key . '/';
            $replacements[$key] = $value;
        }
        $this->parameters = preg_replace(
            $patterns,
            $replacements,
            $this->parameters
        );
        return $this;
    }

}

class RouteNotFoundError extends Error {}
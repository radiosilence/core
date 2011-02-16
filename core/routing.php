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
import('core.utils.ipv4');

if(extension_loaded('memcached')) {
    import('core.backend.memcached');
    define('MC_AVAIL', True);
}

class Router extends Contained {
    private $_path;
    private $_routes = array();
    private $_memcached_available = False;

    public function __construct($routes_file=null) {
            
        if(empty($routes_file)) {
            $routes_file = SITE_PATH . '/routes.php';
        }
        if(!file_exists($routes_file)) {
            throw new FileNotFoundError($routes_file);
        }
        require $routes_file;
        if(!is_array($routes)) {
            throw new RoutingError("No routes defined.");
        }
        foreach($routes as $request => $destination) {
            $this->add_route($request, $destination);
        }
    }
    public function add_route($request, $destination) {
        $this->_routes[$request] = new Route($destination);
    }

    public function route($uri) {
        $uri = $this->_clean_uri($uri);
        $route = $this->_find_route($uri);

        $mc = new \Core\Backend\MemcachedContainer();
        try {
            $m = $mc->get_backend();        
            if($route->parameters['__cache__'] == 'on') {
                $key = "page:route={$uri}";    
            } else if($route->parameters['__cache__'] == 'ip') {
                $key = "page:route={$uri};ip=" . \Core\Utils\IPV4::get();
            }

            if($route->parameters['__cache__'] && $page = $m->get($key)) {
                echo $page;
                return True;
            }
            $m_enable = True;
        } catch(\Core\FileNotFoundError $e) {
            trigger_error("Disabling route cache due to no config file.", \E_USER_WARNING);
        } catch(\Core\Error $e) {
            trigger_error("Disabling route cache due to no memcached section in config.", \E_USER_WARNING);
        }
        import('controllers.' . strtolower($route->class));
        $class = sprintf('\Controllers\%s', str_replace('.', '\\', $route->class));

        $controller = new $class($route->parameters);
        $method = $route->parameters['method'];
        if(!method_exists($controller, $method)) {
            $method = 'index';
        }
        ob_start();
        $controller->$method();
        $page = ob_get_contents();
        if($m_enable) {
            $m->set($key, $page, time()+60);        
        }
    }

    private function _clean_uri($uri) {
        if(substr($uri, 0, 1) == '/') {
            $uri = substr($uri, 1);
        }
        if(substr($uri, -1) == '/') {
            $uri = substr($uri, 0, -1);
        }
        return $uri;
    }

    private function _find_route($uri) {
        $xsrf_pattern = '/\/?xsrf:([0-9]+)/';
        preg_match($xsrf_pattern, $uri, $reqid);    
        $uri = preg_replace($xsrf_pattern, '', $uri);
        $reqid = array_pop($reqid);
        foreach($this->_routes as $potential => $route) {
            $potential = str_replace('/', '\/', $potential);
            if(preg_match('/\/?' . $potential . '/', $uri, $matches)) {
                $route->__antixsrf_reqid__ = $reqid;
                return $route->assign_vars($matches);
            }
        }
        throw new HTTPError(404, $uri);
    }
}

class Route extends Dict {
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

        foreach($this->__data__ as $k => $v) {
            $this->parameters[$k] = $v;
        }
        return $this;
    }

}
class RoutingError extends Error{}
class RouteNotFoundError extends RoutingError {}
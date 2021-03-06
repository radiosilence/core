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
import('core.backend.memcached');
import('core.utils.env');

class Router extends Contained {
    private $_path;
    private $_routes = array();
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
        try {
            if(!isset($route->parameters['__cache__'])) {
                throw new CacheNotEnabledError();
            }
            $m = \Core\Backend\Memcached::container()
                ->get_backend();
            $m->setOption(\Memcached::OPT_COMPRESSION, False);
            if($route->parameters['__cache__'] == 'on') {
                $key = sprintf("site:%s:uri:%s", \Core\Utils\Env::site_name(), $_SERVER['REQUEST_URI']);
            }

            if($route->parameters['__cache__'] && $page = $m->get($key) && count($_POST) == 0) {
                trigger_error(sprintf("Cache hit but for key [%s] not used by proxy server.", $key), \E_USER_WARNING);
            } else {
                trigger_error("Cache miss.", \E_USER_WARNING);
            }
            $m_enable = True;
        } catch(\Core\FileNotFoundError $e) {
            trigger_error("Disabling route cache due to no config file.", \E_USER_WARNING);
        } catch(\Core\Error $e) {
            trigger_error("Disabling route cache due to no memcached section in config.", \E_USER_WARNING);
        } catch(CacheNotEnabledError $e) {
        } catch(\Core\Backend\MemcachedNotLoadedError $e) {}
        
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
        return filter_var($uri, FILTER_SANITIZE_STRING);
    }

    private function _find_route($uri) {
        foreach($this->_routes as $potential => $route) {
            $potential = str_replace('/', '\/', $potential);
            if(preg_match('/\/?' . $potential . '/', $uri, $matches)) {
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
        $this->parameters['__uri__'] = $_SERVER['REQUEST_URI'];
        return $this;
    }

}
class RoutingError extends Error{}
class RouteNotFoundError extends RoutingError {}
class CacheNotEnabledError extends \Core\SkipException{}
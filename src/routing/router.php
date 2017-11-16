<?php

namespace Penny;

use Penny\RouterException;
use Penny\Config;

class Router {
    private $request;
    private $found_route;
    private $found_route_as_array;
    private $autoload_files = [];
    private $request_configuration;
    private $request_routes = [];
    private $request_routes_as_routes = [];

    public function __construct($request) {
        $this->request = $request;
        $this->findRouteQuery();
        if ($this->request->type() == 'view') $this->loadSiteRoutes();
        elseif ($this->request->type() == 'api' || $this->request->method() === 'cli') {
            $this->loadApiRoutes();
        }

        $this->makeRoutes();

        return $this;
    }

    /**
     * Finds the route information from the request
     *
     * @return Penny\Router
     */
    public function findRouteQuery() {
        if ($this->request->method() === 'cli') {
            $req_vars = $this->request->variables();
            $this->found_route = $req_vars;
            array_splice($req_vars, 0, 1);
            $this->found_route_as_array = $req_vars;
        } elseif (!isset($this->request->variables()['pennyRoute'])) {
            throw new RouterException('Cannot find the route.');
        } else {
            $route = ltrim($this->request->variables()['pennyRoute'], '/');
            if (substr($route, -1) == '/') $route = substr($route, 0, -1);
            $route = preg_replace('/\/+/', '/', $route);
            $this->found_route = $route;
            $this->found_route_as_array = explode('/', $route);

            if (!Config::loaded()) {
                throw new RouterException('The configuration hasn\'t been loaded.');
            } elseif (!Config::hasValue('apiIdentity') || empty($this->found_route_as_array)) {
                $this->request->setType('view');
            } elseif ($this->found_route_as_array[0] === Config::get('apiIdentity')) {
                $this->request->setType('api');
                array_shift($this->found_route_as_array);
            } else $this->request->setType('view');
        }
        return $this;
    }

    /**
     * Returns the route that was found
     *
     * @return string
     */
    public function route() {
        return $this->found_route;
    }

    /**
     * Returns the route that was found as an array
     *
     * @return array
     */
    public function routeAsArray() {
        return $this->found_route_as_array;
    }

    /**
     * Loads the site-specific routes
     *
     * @return null
     */
    public function loadSiteRoutes() {
        if (!Config::loaded()) return;
        $site_path = Config::forSite($this->request->site())['folder'];
        if (!file_exists(REL_ROOT.'sites/'.$site_path.'/config.json')) {
            throw new RouterException('The site configuration information doesn\'t exist.');
        } else {
            $config = file_get_contents(REL_ROOT.'sites/'.$site_path.'/config.json');
            if (!isJSON($config)) throw new RouterException('Invalid site configuration setup.');
            else {
                $this->request_configuration = json_decode($config, true);
                $this->request_routes = $this->request_configuration['routes'];
            }
        }
    }

    /**
     * Loads the API configuration
     *
     * @return null
     */
    public function loadApiRoutes($file = 'config.json') {
        if (!Config::loaded()) return;
        if (!file_exists(REL_ROOT.'apis/'.$file)) {
            throw new RouterException('The api configuration information doesn\'t exist.');
        } else {
            $config = file_get_contents(REL_ROOT.'apis/'.$file);
            if (!isJSON($config)) throw new RouterException('Invalid api configuration setup.');
            else {
                $req_config = json_decode($config, true);
                $this->request_configuration = $req_config;
                $this->addConfigVariables();
                if (isset($req_config['autoload'])) {
                    $this->autoload_files = array_merge($this->autoload_files, $req_config['autoload']);
                }
                $this->request_routes = array_merge($this->request_routes, $req_config['routes']);

                if (isset($req_config['forwards'])) {
                    foreach ($req_config['forwards'] as $forward) $this->loadApiRoutes($forward);
                }
            }
        }

        $this->autoloadFiles();
    }

    /**
     * Adds custom config variables to the config static object
     *
     * @return void
     */
    private function addConfigVariables() {
        if (isset($this->request_configuration['add-config'])) {
            foreach ($this->request_configuration['add-config'] as $key => $val) {
                Config::add($key, $val);
            }
        }
    }

    /**
     * Turns route data into route objects
     *
     * @return void
     */
    private function makeRoutes() {
        foreach ($this->request_routes as $route => $data) {
            $this->request_routes_as_routes[] = new Route($this->request, $route, $data, $this->found_route_as_array);
        }
    }

    /**
     * Autoloads files setup by the API config
     *
     * @return void
     */
    public function autoloadFiles() {
        if (isset($this->autoload_files)) {
            foreach ($this->autoload_files as $class => $path) {
                autoload($class, $path);
            }
        }
    }

    /**
     * Returns the request config, for either the API or view
     *
     * @return array
     */
    public function config() {
        return $this->request_configuration;
    }

    /**
     * Returns all of the found routes as route objects
     *
     * @return array
     */
    public function routes() {
        return $this->request_routes_as_routes;
    }

    /**
     * Returns all of the found routes as an array
     *
     * @return array
     */
    public function routesAsArray() {
        return $this->request_routes;
    }

    /**
     * Finds the route match
     *
     * @return Penny\Route|null
     */
    public function getMatch() {
        foreach ($this->request_routes_as_routes as $route) {
            if ($route->matches()) {
                if (isset($this->request_configuration['routes']['/'.$route->toString()])) {
                    $route_config = $this->request_configuration['routes']['/'.$route->toString()];
                } elseif ($this->request_configuration['routes'][$route->toString()]) {
                    $route_config = $this->request_configuration['routes'][$route->toString()];
                } else $route_config = [];

                if (isset($route_config['autoload'])) {
                    $this->autoload_files = array_merge($this->autoload_files, $route_config['autoload']);
                }

                $this->request->addVariables($route->variables());
                $this->autoloadFiles();
                return $route;
            }
        }

        return null;
    }
}

<?php

namespace Penny;

use Penny\RouterException;
use Penny\Config;

class Router {
    private $request;
    private $found_route;
    private $found_route_as_array;
    private $autoload_files = [];
    private $request_configuration = ["add-config" => []];
    private $request_routes = [];
    private $request_routes_as_routes = [];
    public $response_code = 200;

    public function __construct($request) {
        $this->request = $request;

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
            $route = clean_slashes(ltrim($this->request->variables()['pennyRoute'], '/'));
            if (substr($route, -1) == '/') $route = substr($route, 0, -1);
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
     * Formats the route data, adding prefixes and collecting middleware actions
     *
     * @param array $req_config The request configuration
     * @return array Array of routes
     */
    private function formatRouteData($req_config) {
        $routes = [];
        foreach ($req_config['routes'] as $key => $route) {
            if (isset($req_config['routePrefix']) && $this->request->method() != "cli") $route['prefixed'] = true;

            if (isset($req_config['globalMiddlewareActions'])) {
                if (isset($route['middlewareAction'])) {
                    if (is_array($route['middlewareAction'])) {
                        $route['middlewareAction'] = array_merge($route['middlewareAction'], $req_config['globalMiddlewareActions']);
                    } else {
                        $route['middlewareAction'] = array_merge([$route['middlewareAction']], $req_config['globalMiddlewareActions']);
                    }
                } else {
                    $route['middlewareAction'] = $req_config['globalMiddlewareActions'];
                }
            }

            if (isset($req_config['routePrefix']) && $this->request->method() != "cli") {
                $routes[clean_slashes($req_config['routePrefix'].$key)] = $route;
            } else {
                $routes[$key] = $route;
            }
        }

        return $routes;
    }

    /**
     * Autoloads file specific to the route
     *
     * @param array $req_config Request configuration data
     * @param string $site_path Name of the site folder
     */
    private function autoload_route_files($req_config, $site_path) {
        if (isset($req_config['autoload'])) {
            $this->get_autoload_files($req_config['autoload'], Config::apiFolder());
        }

        if (isset($req_config['middleware']) && $site_path != "api") {
            $this->get_autoload_files($req_config['middleware'], Config::siteFolder($site_path));
        }
    }

    /**
     * Loads the site-specific routes
     *
     * @return null
     */
    public function loadSiteRoutes($file = "config.json", $globalMiddlewareActions = []) {
        if (!Config::loaded()) return;
        $site_path = Config::forSite($this->request->site())['folder'];
        $dist_config_path = REL_ROOT.Config::siteFolder($site_path)."/dist/config.dist.json";
        if (file_exists($dist_config_path)) $config_path = $dist_config_path;
        else $config_path = REL_ROOT.Config::siteFolder($site_path)."/".$file;

        if (!file_exists($config_path)) {
            throw new RouterException('The site configuration information doesn\'t exist.');
        } else {
            $config = file_get_contents($config_path);
            if (!isJSON($config)) throw new RouterException('Invalid site configuration setup.');
            else {
                $req_config = json_decode($config, true);
                $this->request_configuration = $req_config;

                if (!isset($req_config['globalMiddlewareActions'])) $req_config['globalMiddlewareActions'] = [];
                if (isset($req_config['globalMiddlewareActions']) && !empty($req_config['globalMiddlewareActions'])) {
                    $req_config['globalMiddlewareActions'] = array_merge($req_config['globalMiddlewareActions'], $globalMiddlewareActions);
                } else {
                    $req_config['globalMiddlewareActions'] = $globalMiddlewareActions;
                }

                $this->request_routes = array_merge($this->request_routes, $this->formatRouteData($req_config));
                $this->autoload_route_files($req_config, $site_path);

                if (isset($req_config['forwards'])) {
                    foreach ($req_config['forwards'] as $forward) {
                        $this->loadSiteRoutes($forward, $req_config['globalMiddlewareActions']);
                    }
                }
            }
        }

        return $this->request_routes;
    }

    /**
     * Adds files to be loaded automatically PSR-4
     *
     * @param  array  $al_array
     * @param  string  $folder
     * @return null
     */
    private function get_autoload_files($al_array, $folder) {
        $af = [];
        foreach ($al_array as $namespace => $path) {
            $af[$namespace] = REL_ROOT.$folder."/".$path;
        }

        $this->autoload_files = array_merge($this->autoload_files, $af);
    }

    /**
     * Loads the API configuration
     *
     * @return null
     */
    public function loadApiRoutes($file = 'config.json', $globalMiddlewareActions = []) {
        if (!Config::loaded()) return;
        if (!file_exists(REL_ROOT.Config::apiFolder().$file)) {
            throw new RouterException('The api configuration information doesn\'t exist.');
        } else {
            $config = file_get_contents(REL_ROOT.Config::apiFolder().$file);
            if (!isJSON($config)) throw new RouterException('Invalid api configuration setup.');
            else {
                $req_config = json_decode($config, true);
                $this->request_configuration = $req_config;
                $this->addConfigVariables();
                if (isset($req_config['autoload'])) {
                    $this->get_autoload_files($req_config['autoload'], Config::apiFolder());
                }

                if (!isset($req_config['globalMiddlewareActions'])) $req_config['globalMiddlewareActions'] = [];
                if (isset($req_config['globalMiddlewareActions']) && !empty($req_config['globalMiddlewareActions'])) {
                    $req_config['globalMiddlewareActions'] = array_merge($req_config['globalMiddlewareActions'], $globalMiddlewareActions);
                } else {
                    $req_config['globalMiddlewareActions'] = $globalMiddlewareActions;
                }

                $this->request_routes = array_merge($this->request_routes, $this->formatRouteData($req_config));
                $this->autoload_route_files($req_config, "api");

                if (isset($req_config['forwards'])) {
                    foreach ($req_config['forwards'] as $forward) {
                        $this->loadApiRoutes($forward, $req_config['globalMiddlewareActions']);
                    }
                }
            }
        }

        $this->autoloadFiles();
        return $this->request_routes;
    }

    /**
     * Adds a key => value variable to the request configuration
     *
     * @param string $key Key for the value
     * @param string $value Value to add to the config
     */
    public function addRequestConfigVariable($key, $value) {
        $this->request_configuration["add-config"][$key] = $value;
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
    public function makeRoutes() {
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
            $this->autoloadFiles();
            if ($route->matches()) {
                $path = "/".$route->toString();
                if ($path == isset($this->request_configuration['routePrefix'])) $path .= "/";

                $this->request->addVariables($route->variables());
                $this->autoloadFiles();
                return $route;
            } else {
                if (is_numeric($route->error_code)) {
                    $this->response_code = $route->error_code;
                    return null;
                }
            }
        }

        return null;
    }
}

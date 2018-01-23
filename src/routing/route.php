<?php

namespace Penny;

use Basically\Crud;
use Basically\Errors;

class Route {
    private $request;
    private $route_string;
    private $route_data = [];
    private $uri_route;
    private $found_variables = [];
    private $load_file;
    private $using_view_file = true;
    public $error_code;

    public function __construct($request, $route_string, $data, $uri_route) {
        $this->request = $request;
        if (substr($route_string, 0, 1) == '/') $route_string = ltrim($route_string, '/');
        if (substr($route_string, -1) == '/') $route_string = substr($route_string, 0, -1);
        $this->route_string = clean_slashes($route_string);
        $this->route_data = $data;
        $this->uri_route = $uri_route;
        return $this;
    }

    /**
     * Returns the path as a string
     *
     * @return string
     */
    public function toString() {
        return $this->route_string;
    }

    /**
     * Returns the data related to the route
     *
     * @return array
     */
    public function data() {
        return $this->route_data;
    }

    /**
     * Creates/Updates a data item for the route
     *
     * @param string|int $key
     * @param any $value
     */
    public function setData($key, $value) {
        $this->route_data[$key] = $value;
    }

    /**
     * Creates/Updates a data variable for the route
     *
     * @param string|int $key
     * @param any $value
     */
    public function setVariable($key, $value) {
        $this->route_data['vars'][$key] = $value;
    }

    /**
     * Returns a specific variable for the route
     *
     * @param  string|int  $key
     * @return any
     */
    public function variable($key) {
        return $this->route_data['vars'][$key];
    }

    /**
     * Returns the variables found in the URI
     *
     * @return array
     */
    public function variables() {
        return $this->found_variables;
    }

    /**
     * Checks this route's route vs the route from the URI
     *
     * @return bool
     */
    public function matches() {
        if ($this->request->method() === 'cli') return $this->matchCli();
        else if ($this->request->method() !== 'cli' && isset($this->route_data['cli'])) return false;
        else return $this->matchView();
    }

    /**
     * Checks to see if the config method matches the request method
     *
     * @return bool
     */
    private function checkMethod() {
        if (isset($this->route_data['method'])) {
            return strtolower($this->route_data['method']) == $this->request->method();
        }
        return true;
    }

    /**
     * Matches when an HTTP request is made
     *
     * @return bool
     */
    private function matchView() {
        if (!$this->checkMethod()) return false;

        $route = explode('/', $this->route_string);
        $expected_length = count($this->uri_route);
        $total_length = count($route);

        if ($total_length < $expected_length) return false;

        for ($i = 0; $i < $total_length; $i++) {
            if ($route[$i] == '*') continue;

            if (substr($route[$i], 0, 1) === '{' && substr($route[$i], -1) === '}') {
                if (!isset($this->uri_route[$i])) continue;
                $this->found_variables[str_replace(['{', '}'], "", $route[$i])] = $this->uri_route[$i];
                continue;
            }

            if (!isset($this->uri_route[$i])) return false;
            if ($route[$i] !== $this->uri_route[$i]) return false;
        }

        $this->validateVariables();

        if (isset($this->route_data['file'])) {
            $this->load_file = REL_ROOT.Config::siteFolder($this->request->site()).'/'.$this->route_data['file'];
            if (!file_exists($this->load_file)) return false;
            $this->using_view_file = false;
        }

        $results = [];
        if (isset($this->route_data['middlewareAction'])) {
            if (is_array($this->route_data['middlewareAction'])) {
                foreach ($this->route_data['middlewareAction'] as $action_path) {
                    $results[] = $this->runMiddlewareAction($action_path);
                }
            } else {
                $results[] = $this->runMiddlewareAction($this->route_data['middlewareAction']);
            }
        }

        if (isset($this->route_data['vars'])) $this->request->addVariables($this->route_data['vars']);

        if (empty($results)) return true;
        else {
            foreach ($results as $result) {
                if (is_numeric($result)) {
                    $this->error_code = $result;
                }
            }


            return (!in_array(false, $results) && $this->error_code == null);
        }
    }

    /**
     * Runs a middleware action path (e.g. Users\\SessionController::require_login)
     *
     * @param  string  $action_path
     * @return boolean defined by the output of the function, should be TRUE or FALSE
     */
    private function runMiddlewareAction($action_path) {
        $action = $this->middlewareActionExists($action_path);
        if (!$action) return false;

        return call_user_func_array([$action[0], $action[1]], [$this]);
    }

    /**
     * Validates that the middleware action specified exists
     *
     * @return bool
     */
    public function middlewareActionExists($action) {
        $action_data = explode('::', $action);
        $class = $action_data[0];
        $method = $action_data[1];

        if (!class_exists($class)) {
            throw new ResponseException('Middleware class hasn\'t been loaded, or doesn\'t exist.');
        }

        if (!method_exists($class, $method)) {
            throw new ResponseException('Middleware method doesn\'t exist');
        }

        return [$class, $method];
    }

    /**
     * Finds all of the variables requested by the route and validates them
     *
     * @return void
     */
    private function validateVariables() {
        if (!isset($this->route_data['variables'])) return;

        $req_vars = array_merge($this->found_variables, $this->request->variables());
        foreach ($this->route_data['variables'] as $key => $val) {
            $required = !isset($val['required']) ? false : $val['required'];
            $errors = !isset($val['errors']) ? [] : $val['errors'];
            $match = !isset($val['match']) ? [] : $val['match'];

            if (!isset($req_vars[$key]) && $required) {
                if (isset($errors['missing'])) {
                    if (is_array($errors['missing'])) {
                        if (isset($errors['missing']['code'])) {
                            throw new ResponseException($errors['missing']['message'], $errors['missing']['code']);
                        } else {
                            throw new ResponseException($errors['missing']['message']);
                        }
                    } else throw new ResponseException($errors['missing']);
                }
                else throw new ResponseException('Missing variable "'.$key.'".', 0);
            } elseif (isset($req_vars[$key])) {
                // $custom_errors = $this->buildCustomErrors($errors);
                if (isset($match['values'])) $match = array_merge($match['values'], $match);
                unset($match['values']);
                // $this->found_variables[$key] = CRUD::sanitize($req_vars[$key], $match, $custom_errors);
                $this->found_variables[$key] = $req_vars[$key];
            } elseif (!$required) $this->found_variables[$key] = null;
        }
    }

    /**
     * Builds custom errors for API route variables
     *
     * @param  array $errors
     * @return Basically\Errors;
     */
    private function buildCustomErrors($errors) {
        $index = [
            'missing'     => 'setWhenMissingRequired',
            'notstring'   => 'setWhenNotString',
            'tooshort'    => 'setWhenShortString',
            'toolong'     => 'setWhenLongString',
            'mismatch'    => 'setWhenStringMatch',
            'bademail'    => 'setWhenBadEmail',
            'notnumber'   => 'setWhenNotNumber',
            'baddate'     => 'setWhenBadDate',
            'badname'     => 'setWhenBadName',
            'notbool'     => 'setWhenNotBoolean' ];

        $custom = new Errors();
        foreach ($errors as $error => $data) {
            $function = $index[$error];
            if (is_array($data)) {
                if (!isset($data['code'])) {
                    $custom->$function($data['message']);
                } else {
                    $custom->$function($data['message'], $data['code']);
                }
            } else {
                $custom->$function($data);
            }
        }

        return $custom;
    }

    /**
     * Matches when a CLI request is made
     *
     * @return bool
     */
    private function matchCli() {
        $command = $this->route_string;
        return $command === $this->uri_route[0];
    }

    /**
     * Returns the variables related to the request
     *
     * @return array
     */
    public function requestVars() {
        return $this->request->variables();
    }

    /**
     * Returns the value if the route is for a real file
     *
     * @return bool
     */
    public function forFile() {
        return !$this->using_view_file;
    }

    /**
     * Returns the value of the routes real file path
     *
     * @return string
     */
    public function file() {
        return $this->load_file;
    }
}

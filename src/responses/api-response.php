<?php

namespace Penny;
use Basically\Crud;
use Basically\Errors;

class ApiResponse {
    private $route;
    private $config;
    private $request;
    private $action;
    private $found_variables = [];
    private $method_matches = true;

    public function __construct($route, $config, $request = null) {
        $this->route = $route;
        $this->config = $config;
        $this->request = $request;

        if ($route !== null) {
            $this->actionExists();
        }
    }

    /**
     * Validates that the action specified exists
     *
     * @return bool
     */
    public function actionExists() {
        $action_data = explode('::', $this->route->data()['action']);
        $class = $action_data[0];
        $method = $action_data[1];

        if (!class_exists($class)) {
            throw new ResponseException('Cannot run API action because the class hasn\'t been loaded, or doesn\'t exist.');
        }

        if (!method_exists($class, $method)) {
            throw new ResponseException('Cannot run the API action because the method doesn\'t exist');
        }

        $this->action = [$class, $method];
        return true;
    }

    /**
     * Returns the validated route variables
     *
     * @return array
     */
    public function variables() {
        return $this->route->variables();
    }

    /**
     * Answers the request
     *
     * @param  array   $args
     * @param  boolean $auto_echo
     * @return void
     */
    public function respond($args = [], $auto_echo = true) {
        call_user_func_array([$this->action[0], $this->action[1]], $args);
        if ($auto_echo) echo JSON::get();
    }

    /**
     * Responds with an error when the API doesn't exist.
     *
     * @param  number $code
     * @return void
     */
    public function error($code) {
        http_response_code(404);
    }
}

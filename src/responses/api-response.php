<?php

namespace Penny;

class ApiResponse {
    private $route;
    private $config;
    private $request;
    private $action;

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

        $this->setAction($class, $method);
        return true;
    }

    /**
     * Defines the class and method of the action that will be run
     *
     * @param string $class - Name of the class
     * @param string $method - Name of the method
     */
    public function setAction($class, $method) {
        $this->action = [$class, $method];
    }

    /**
     * Returns the name of the action class
     *
     * @return string - Name of the action class
     */
    public function actionClass() {
        return $this->action[0];
    }

    /**
     * Returns the name of the action method
     *
     * @return string - Name of the action method
     */
    public function actionMethod() {
        return $this->action[1];
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
        call_user_func_array([$this->actionClass(), $this->actionMethod()], $args);
        if ($auto_echo) echo JSON::get();
    }

    /**
     * Responds with an error when the API doesn't exist.
     *
     * @param  number $code
     * @return void
     */
    public function error($code) {
        http_response_code($code);
    }
}

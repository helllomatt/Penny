<?php

use Penny\ApiResponse;
use Penny\Route;
use Penny\Request;
use Penny\Config;
use Penny\Router;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase {
    public static function setUpBeforeClass() {
        Config::load('./tests/sample-config.json');
    }

    public function testCheckingAction() {
        $request = (new Request(['pennySite' => 'defaultSite', 'pennyRoute' => '/api/test']))->overrideMethod('get')->findSite();
        $router = new Router($request);
        $route = new Route($request, '/test', ["action" => "Test\\Greeting::say_hello"], ['test']);
        $ar = new ApiResponse($route, ['apiIdentity' => 'api']);
        $this->assertTrue($ar->actionExists());
    }

    public function testCheckingActionUnloadedClass() {
        $request = (new Request(['pennySite' => 'defaultSite', 'pennyRoute' => '/api/test']))->overrideMethod('get')->findSite();
        $router = new Router($request);
        $route = new Route($request, '/test', ["action" => "asdf::say_hello"], ['test']);
        $this->expectException('Penny\ResponseException');
        $ar = new ApiResponse($route, ['apiIdentity' => 'api']);
    }

    public function testCheckingActionBadMethod() {
        $request = (new Request(['pennySite' => 'defaultSite', 'pennyRoute' => '/api/test']))->overrideMethod('get')->findSite();
        $router = new Router($request);
        $route = new Route($request, '/test', ["action" => "Test\\Greeting::asdf"], ['test']);
        $this->expectException('Penny\ResponseException');
        $ar = new ApiResponse($route, ['apiIdentity' => 'api']);
    }
}

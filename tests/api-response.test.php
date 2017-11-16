<?php

use Penny\ApiResponse;
use Penny\Config;
use PHPUnit\Framework\TestCase;

class ApiResponseTest extends TestCase {
    public static function setUpBeforeClass() {
        Config::load('./tests/sample-config.json');
    }

    public function testCheckingAction() {
        include "tests/includes/test.class.php";
        $route = $this->createMock("Penny\Route");
        $route->method("data")->willReturn(['action' => "Test\Greeting::say_hello"]);
        $ar = new ApiResponse($route, ['apiIdentity' => 'api']);
        $this->assertTrue($ar->actionExists());
    }

    public function testCheckingActionUnloadedClass() {
        $route = $this->createMock("Penny\Route");
        $route->method("data")->willReturn(['action' => "Test\Undefined::say_hello"]);
        $this->expectException('Penny\ResponseException');
        $ar = new ApiResponse($route, ['apiIdentity' => 'api']);
    }

    public function testCheckingActionBadMethod() {
        $route = $this->createMock("Penny\Route");
        $route->method("data")->willReturn(['action' => "Test\Greeting::say_undefined"]);
        $this->expectException('Penny\ResponseException');
        $ar = new ApiResponse($route, ['apiIdentity' => 'api']);
    }
}

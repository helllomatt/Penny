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
        new ApiResponse($route, ['apiIdentity' => 'api']);
    }

    public function testCheckingActionBadMethod() {
        $route = $this->createMock("Penny\Route");
        $route->method("data")->willReturn(['action' => "Test\Greeting::say_undefined"]);
        $this->expectException('Penny\ResponseException');
        new ApiResponse($route, ['apiIdentity' => 'api']);
    }

    public function testSettingAction() {
        $ar = new ApiResponse(null, ["apiIdentity" => "api"]);
        $ar->setAction("Test\Greeting", "say_goodbye");

        $this->assertEquals("Test\Greeting", $ar->actionClass());
        $this->assertEquals("say_goodbye", $ar->actionMethod());
    }

    public function testGettingRouteVariables() {
        $route = $this->createMock("Penny\Route");
        $route->method("data")->willReturn(['action' => "Test\Greeting::say_hello"]);
        $route->method("variables")->willReturn(["key" => "value"]);

        $ar = new ApiResponse($route, ["apiIdentity" => "api"]);
        $this->assertEquals(["key" => "value"], $ar->variables());
    }

    public function testRunningResponse() {
        $route = $this->createMock("Penny\Route");
        $route->method("data")->willReturn(['action' => "Test\Greeting::echo_hello"]);

        $this->expectOutputString("Hello, World!");
        $ar = new ApiResponse($route, ["apiIdentity" => "api"]);
        $ar->respond([], false);
    }

    public function testPassingDataToAction() {
        $route = $this->createMock("Penny\Route");
        $route->method("data")->willReturn(['action' => "Test\Greeting::echo_hello_name"]);

        $this->expectOutputString("Hello, Matt!");
        $ar = new ApiResponse($route, ["apiIdentity" => "api"]);
        $ar->respond(["Matt"], false);
    }

    public function testRunningJSONResponse() {
        $route = $this->createMock("Penny\Route");
        $route->method("data")->willReturn(['action' => "Test\Greeting::say_hello"]);

        $ar = new ApiResponse($route, ["apiIdentity" => "api"]);
        $ar->respond([], false);
    }

    public function testHttpError() {
        $ar = new ApiResponse(null, []);
        $ar->error(404);
        $this->assertEquals(404, http_response_code());
    }
}

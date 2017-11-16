<?php

use Penny\Request;
use Penny\Router;
use Penny\Config;
use PHPUnit\Framework\TestCase;

class RouterTests extends TestCase {
    public static function setUpBeforeClass() {
        Config::load('./tests/sample-config.json');
    }

    public function testExceptionWhenFindingRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $this->expectException('Penny\RouterException');
        $router = new Router($request);
    }

    public function testFindingRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'index']);
        $router = new Router($request);

        $this->assertEquals('index', $router->route());
    }

    public function testFindingFormattedRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'index/']);
        $router = new Router($request);

        $this->assertEquals('index', $router->route());
    }

    public function testReplacingMultipleSlashesInRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'index////test']);
        $router = new Router($request);

        $this->assertEquals('index/test', $router->route());
    }

    public function testGettingRouteAsArray() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'index/test']);
        $router = new Router($request);

        $this->assertEquals(['index', 'test'], $router->routeAsArray());
    }

    public function testAutoloadingApiFiles() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => '/api/say-hello/']);
        $router = new Router($request);

        $greeting = new Test\Greeting();
        $this->assertInstanceOf('Test\Greeting', $greeting);
    }
}

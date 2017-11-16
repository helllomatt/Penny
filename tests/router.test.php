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
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $this->expectException('Penny\RouterException');
        $router = new Router($request);
    }

    public function testFindingRoute() {
        $request = (new Request(['pennyRoute' => 'index', 'pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $router = new Router($request);

        $this->assertEquals('index', $router->route());
    }

    public function testFindingFormattedRoute() {
        $request = (new Request(['pennyRoute' => 'index/', 'pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $router = new Router($request);

        $this->assertEquals('index', $router->route());
    }

    public function testReplacingMultipleSlashesInRoute() {
        $request = (new Request(['pennyRoute' => 'index////test', 'pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $router = new Router($request);

        $this->assertEquals('index/test', $router->route());
    }

    public function testGettingRouteAsArray() {
        $request = (new Request(['pennyRoute' => 'index/test', 'pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $router = new Router($request);

        $this->assertEquals(['index', 'test'], $router->routeAsArray());
    }

    public function testGettingSiteRoutes() {
        $request = (new Request(['pennySite' => 'defaultSite', 'pennyRoute' => '/']))->overrideMethod('get')->findSite();
        $router = new Router($request);

        $this->assertTrue(array_key_exists('/', $router->routesAsArray()));
    }

    public function testRequstingAPI() {
        $request = (new Request(['pennySite' => 'defaultSite', 'pennyRoute' => '/api/say-hello/']))->overrideMethod('get')->findSite();
        $router = new Router($request);

        $this->assertEquals('api', $request->type());
    }

    public function testAutoloadingApiFiles() {
        $request = (new Request(['pennySite' => 'defaultSite', 'pennyRoute' => '/api/say-hello/']))->overrideMethod('get')->findSite();
        $router = new Router($request);

        $greeting = new Test\Greeting();
        $this->assertInstanceOf('Test\Greeting', $greeting);
    }
}

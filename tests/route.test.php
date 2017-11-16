<?php

use Penny\Route;
use Penny\Request;
use Penny\Router;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase {
    public function testSettingRouteUp() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/', ['view' => 'test.view.php'], '');

        $this->assertEquals('', $route->toString());
        $this->assertEquals(['view' => 'test.view.php'], $route->data());
    }

    public function testComparingRouteIndex() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/', [], ['']);
        $this->assertTrue($route->matches());
    }

    public function testComparingRoute() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/asdf', [], ['asdf']);
        $this->assertTrue($route->matches());
    }

    public function testComparingLongRoute() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/thing1/thing2/thing3/', [], ['thing1', 'thing2', 'thing3']);
        $this->assertTrue($route->matches());
    }

    public function testComparingNoMatch() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/', [], ['asdf']);
        $this->assertFalse($route->matches());
    }

    public function testComparingWithVariable() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/thing1/{var}', [], ['thing1', 'thing2']);
        $this->assertTrue($route->matches());
    }

    public function testComparingWithTooManyVariables() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/thing1/{var}', [], ['thing1', 'thing2', 'thing3']);
        $this->assertFalse($route->matches());
    }

    public function testGettingVariablesFromRoute() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/thing1/{var}', [], ['thing1', 'thing2']);
        $route->matches();

        $this->assertEquals(['var' => 'thing2'], $route->variables());
    }

    public function testGettingCLI() {
        $request = new Request();
        $route = new Route($request, 'thing', ['thing'], ['thing']);

        $this->assertTrue($route->matches());
    }
}

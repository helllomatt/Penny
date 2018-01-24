<?php

use Penny\Request;
use Penny\Router;
use Penny\Config;
use PHPUnit\Framework\TestCase;

class RouterTests extends TestCase {
    public static function setUpBeforeClass() {
//        Config::load('./tests/sample-config.json');
    }
    /**
    * Call protected/private method of a class.
    *
    * @param object &$object    Instantiated object that we will run method on.
    * @param string $methodName Method name to call
    * @param array  $parameters Array of parameters to pass into method.
    *
    * @return mixed Method return.
    */
   public function invokeMethod(&$object, $methodName, array $parameters = array()) {
       $reflection = new \ReflectionClass(get_class($object));
       $method = $reflection->getMethod($methodName);
       $method->setAccessible(true);

       return $method->invokeArgs($object, $parameters);
   }

    public function testExceptionWhenFindingRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $router = new Router($request);
        $this->expectException('Penny\RouterException');
        $this->invokeMethod($router, "findRouteQuery");
    }

    public function testFindingRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'index']);
        $router = new Router($request);

        $this->invokeMethod($router, "findRouteQuery");
        $this->assertEquals('index', $router->route());
    }

    public function testFindingFormattedRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'index/']);
        $router = new Router($request);

        $this->invokeMethod($router, "findRouteQuery");
        $this->assertEquals('index', $router->route());
    }

    public function testReplacingMultipleSlashesInRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'index////test']);
        $router = new Router($request);

        $this->invokeMethod($router, "findRouteQuery");
        $this->assertEquals('index/test', $router->route());
    }

    public function testGettingRouteAsArray() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'index/test']);
        $router = new Router($request);

        $this->invokeMethod($router, "findRouteQuery");
        $this->assertEquals(['index', 'test'], $router->routeAsArray());
    }

    public function testAutoloadingApiFiles() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => '/api/say-hello/']);
        new Router($request);

        $greeting = new Test\Greeting();
        $this->assertInstanceOf('Test\Greeting', $greeting);
    }

    public function testGettingCliRouteQuery() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("cli");
        $request->expects($this->any())->method("variables")->willReturn([]);
        $router = new Router($request);
        $this->invokeMethod($router, "findRouteQuery");
    }

    public function testConfigNotLoaded() {
        Config::unload();
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'index']);
        $router = new Router($request);
        $this->expectException('Penny\RouterException');
        $this->invokeMethod($router, "findRouteQuery");
    }

    public function testDetectingView() {
        Config::load('./tests/sample-config.json');
        Config::delete("apiIdentity");
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'index']);
        $router = new Router($request);
        $this->invokeMethod($router, "findRouteQuery");
    }

    public function testDetectingApi() {
        Config::load('./tests/sample-config.json');
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $router = new Router($request);
        $this->invokeMethod($router, "findRouteQuery");
    }

    public function testFormattingRouteData() {
        $router = new Router(null);
        $routes = $this->invokeMethod($router, "formatRouteData", [[
            "routes" => [
                "/" => [
                    "view" => "no"
                ]
            ]
        ]]);

        $this->assertEquals(["/" => ["view" => "no"]], $routes);
    }

    public function testFormattingRouteDataWithGlobalMiddleware() {
        $router = new Router(null);
        $routes = $this->invokeMethod($router, "formatRouteData", [[
            "globalMiddlewareActions" => ["Test\Greeting::say_hello"],
            "routes" => [
                "/" => [
                    "view" => "no"
                ]
            ]
        ]]);

        $this->assertEquals(["/" => ["view" => "no", "middlewareAction" => ["Test\Greeting::say_hello"]]], $routes);
    }

    public function testFormattingRouteDataWithGlobalMiddlewareArray() {
        $router = new Router(null);
        $routes = $this->invokeMethod($router, "formatRouteData", [[
            "globalMiddlewareActions" => ["Test\Greeting::say_hello"],
            "routes" => [
                "/" => [
                    "middlewareAction" => [
                        "Test\Greeting::say_hello",
                        "Test\Greeting::say_hello"
                    ],
                    "view" => "no"
                ]
            ]
        ]]);

        $this->assertEquals(["/" => ["view" => "no", "middlewareAction" => [
                        "Test\Greeting::say_hello",
                        "Test\Greeting::say_hello",
                        "Test\Greeting::say_hello"]]], $routes);
    }

    public function testFormattingRouteDataWithRoutePrefix() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("view");
        $router = new Router($request);
        $routes = $this->invokeMethod($router, "formatRouteData", [[
            "routePrefix" => "something/",
            "routes" => [
                "/" => [
                    "view" => "no"
                ]
            ]
        ]]);

        $this->assertEquals(["something/" => ["view" => "no", "prefixed" => true]], $routes);
    }

    public function testAutoloadingApiRouteFiles() {
        $router = new Router(null);
        $this->invokeMethod($router, "autoload_route_files", [["autoload" => ["Test\\" => "/"]], "api"]);
    }

    public function testAutoloadingMiddlewareRouteFiles() {
        $router = new Router(null);
        $this->invokeMethod($router, "autoload_route_files", [["middleware" => ["Test\\" => "/"]], "view"]);
    }

    public function testLoadingSiteRoutes() {
        Config::load('./tests/sample-config.json');
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $router = new Router($request);
        $router->loadSiteRoutes();
    }

    public function testLoadingSiteRoutesBadConfig() {
        Config::load('./tests/sample-config.json');
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $router = new Router($request);
        $this->expectException("Penny\RouterException");
        $router->loadSiteRoutes("no");
    }

    public function testLoadingSiteRoutesInjectedGlobalMiddleware() {
        Config::load('./tests/sample-config.json');
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $router = new Router($request);
        $routes = $router->loadSiteRoutes("config.json", ["Test\\Greeting::say_hello"]);
        $this->assertEquals(["/" => ["view" => "homepage.view.php", "middlewareAction" => ["Test\\Greeting::say_hello"]]], $routes);
    }

    public function testLoadingSiteRoutesGlobalMiddleware() {
        Config::load('./tests/sample-config.json');
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $router = new Router($request);
        $routes = $router->loadSiteRoutes("config-global-mw.json", ["Test\\Greeting::say_hello"]);
        $this->assertEquals(["/" => ["view" => "homepage.view.php", "middlewareAction" => ["Test\\Greeting::say_hello", "Test\\Greeting::say_hello"]]], $routes);
    }

    public function testLoadingSiteRoutesForwarded() {
        Config::load('./tests/sample-config.json');
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $router = new Router($request);
        $routes = $router->loadSiteRoutes("config-forward.json");
        $this->assertEquals(["/forwarded/" => ["view" => "homepage.view.php", "middlewareAction" => [], "prefixed" => true]], $routes);
    }

    public function testLoadingApiRoutes() {
        Config::load("./tests/sample-config.json");
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $router = new Router($request);
        $routes = $router->loadApiRoutes();
        $this->assertEquals(["/" => ["action" => "Test\\Controller::hello", "middlewareAction" => []]], $routes);
    }

    public function testLoadingApiRoutesConfigFileDoesntExist() {
        $router = new Router(null);
        $this->expectException("Penny\RouterException");
        $router->loadApiRoutes("no");
    }

    public function testLoadingApiRoutesWithAutoloadedFiles() {
        Config::load("./tests/sample-config.json");
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $router = new Router($request);
        $routes = $router->loadApiRoutes("config-autoload.json");
        $this->assertEquals([], $routes);
    }

    public function testLoadingApiRoutesWithMiddleware() {
        Config::load("./tests/sample-config.json");
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $router = new Router($request);
        $routes = $router->loadApiRoutes("config-middleware.json");
        $this->assertEquals(["/" => ["action" => "Test\\Controller::hello", "middlewareAction" => ["Test\\Greeting::say_hello"]]], $routes);
    }

    public function testLoadingApiRoutesWithGlobalMiddleware() {
        Config::load("./tests/sample-config.json");
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $router = new Router($request);
        $routes = $router->loadApiRoutes("config-global-middleware.json");
        $this->assertEquals(["/" => ["action" => "Test\\Controller::hello", "middlewareAction" => ["Test\\Greeting::say_hello", "Test\\Greeting::say_hello"]]], $routes);
    }

    public function testLoadingApiRoutesWithForwardingAndRoutePrefixed() {
        Config::load("./tests/sample-config.json");
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $router = new Router($request);
        $routes = $router->loadApiRoutes("config-forwarding.json");
        $this->assertEquals(["/forwarded/" => ["action" => "Test\\Controller::hello", "prefixed" => true, "middlewareAction" => []]], $routes);
    }

    public function testAddingRequestConfiguationVariable() {
        Config::load("./tests/sample-config.json");
        $router = new Router(null);
        $router->addRequestConfigVariable("key", "value");

        $this->invokeMethod($router, "addConfigVariables");
        $this->assertEquals("value", Config::get("key"));
    }

    public function testGettingRequestConfiguration() {
        $router = new Router(null);
        $router->addRequestConfigVariable("key", "value");

        $this->assertEquals(["add-config" => ["key" => "value"]], $router->config());
    }

    public function testGettingRoutesAsRoutes() {
        $router = new Router(null);

        $this->assertEquals([], $router->routes());
    }

    public function testGettingRoutesAsData() {
        $router = new Router(null);

        $this->assertEquals([], $router->routesAsArray());
    }

    public function testMakingRoutes() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => 'api/index']);
        $router = new Router($request);
        $router->loadApiRoutes();

        $router->makeRoutes();
        $routes = $router->routes();

        $this->assertEquals(1, count($routes));
        $this->assertInstanceOf("Penny\Route", $routes[0]);
    }

    public function testGettingMatch() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => '/']);
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $router = new Router($request);
        $router->findRouteQuery();
        $router->loadSiteRoutes();

        $router->makeRoutes();
        $match = $router->getMatch();

        $this->assertInstanceOf("Penny\Route", $match);
    }

    public function testGetting404Match() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => '/']);
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $router = new Router($request);
        $router->findRouteQuery();
        $router->loadSiteRoutes("config-mw-error.json");

        $router->makeRoutes();
        $match = $router->getMatch();

        $this->assertNull($match);
        $this->assertEquals(404, $router->response_code);
    }

    public function testGettingNoMatch() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "variables", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("api");
        $request->expects($this->any())->method("variables")->willReturn(['pennyRoute' => '/asdf']);
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $router = new Router($request);
        $router->findRouteQuery();
        $router->loadSiteRoutes("config-mw-error.json");

        $router->makeRoutes();
        $match = $router->getMatch();

        $this->assertNull($match);
    }
}

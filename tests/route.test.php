<?php

use Penny\Route;
use Penny\Request;
use Penny\Router;
use Penny\Config;
use PHPUnit\Framework\TestCase;

class RouteTest extends TestCase {
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

    public function testSettingRouteUp() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();

        $route = new Route($request, '/', ['view' => 'test.view.php'], '');

        $this->assertEquals('', $route->toString());
        $this->assertEquals(['view' => 'test.view.php'], $route->data());
    }

    public function testComparingRouteIndex() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $route = new Route($request, '/', [], ['']);
        $this->assertTrue($route->matches());
    }

    public function testComparingRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $route = new Route($request, '/asdf', [], ['asdf']);
        $this->assertTrue($route->matches());
    }

    public function testComparingLongRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $route = new Route($request, '/thing1/thing2/thing3/', [], ['thing1', 'thing2', 'thing3']);
        $this->assertTrue($route->matches());
    }

    public function testComparingNoMatch() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $route = new Route($request, '/', [], ['asdf']);
        $this->assertFalse($route->matches());
    }

    public function testComparingWithVariable() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $route = new Route($request, '/thing1/{var}', [], ['thing1', 'thing2']);
        $this->assertTrue($route->matches());
    }

    public function testComparingWithTooManyVariables() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $route = new Route($request, '/thing1/{var}', [], ['thing1', 'thing2', 'thing3']);
        $this->assertFalse($route->matches());
    }

    public function testGettingVariablesFromRoute() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $route = new Route($request, '/thing1/{var}', [], ['thing1', 'thing2']);
        $route->matches();

        $this->assertEquals(['var' => 'thing2'], $route->variables());
    }

    public function testGettingCLI() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $route = new Route($request, 'thing', ['thing'], ['thing']);

        $this->assertTrue($route->matches());
    }

    public function testSettingData() {
        $route = new Route();
        $route->setData("key", "value");
        $data = $route->data();
        $this->assertEquals(["key" => "value"], $data);
    }

    public function testSettingVariable() {
        $route = new Route();
        $route->setVariable("key", "value");

        $this->assertEquals("value", $route->variable("key"));
    }

    public function testCheckingMethod() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("get");

        $route = new Route($request);
        $route->setData("method", "get");

        $this->assertTrue($this->invokeMethod($route, "checkMethod"));
    }

    public function testCheckingBadMethod() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("post");

        $route = new Route($request);
        $route->setData("method", "get");

        $this->assertFalse($this->invokeMethod($route, "checkMethod"));
    }

    public function testMatchingView() {
        Config::load("./tests/sample-config.json");

        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("post");
        $request->expects($this->any())->method("site")->willReturn("test");

        $route = new Route($request, "/realfile.txt", ["file" => "realfile.txt"], ["realfile.txt"]);
        $this->invokeMethod($route, "matchView");
    }

    public function testAddingMiddlewareAction() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("post");
        $request->expects($this->any())->method("site")->willReturn("test");

        $route = new Route($request, "/something", ["middlewareAction" => "Test\Greeting::say_hello"], ["something"]);
        $this->invokeMethod($route, "matchView");
    }

    public function testAddingArrayOfMiddlewareActions() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("post");
        $request->expects($this->any())->method("site")->willReturn("test");

        $route = new Route($request, "/something", ["middlewareAction" => ["Test\Greeting::say_hello", "Test\Greeting::return_404"]], ["something"]);
        $this->invokeMethod($route, "matchView");
    }

    public function testCheckingMiddlewareClassNotExist() {
        $route = new Route();
        $this->expectException("Penny\ResponseException");
        $route->middlewareActionExists("Test\Bad::say_hello");
    }

    public function testCheckingMiddlewareMethodNotExist() {
        $route = new Route();
        $this->expectException("Penny\ResponseException");
        $route->middlewareActionExists("Test\Greeting::no");
    }

    public function testMatchingCliCommand() {
        $route = new Route(null, "test", [], ["test"]);
        $this->assertTrue($this->invokeMethod($route, "matchCli"));
    }

    public function testBadMatchingCliCommand() {
        $route = new Route(null, "test", [], ["hello"]);
        $this->assertFalse($this->invokeMethod($route, "matchCli"));
    }

    public function testGettingRequestVariables() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["variables"])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(["key" => "value"]);

        $route = new Route($request);
        $this->assertEquals(["key" => "value"], $route->requestVars());
    }

    public function testCheckingForRealFile() {
        Config::load("./tests/sample-config.json");

        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("post");
        $request->expects($this->any())->method("site")->willReturn("test");

        $route = new Route($request, "/realfile.txt", ["file" => "realfile.txt"], ["realfile.txt"]);
        $this->invokeMethod($route, "matchView");

        $this->assertTrue($route->forFile());
    }

    public function testGettingLoadFile() {
        Config::load("./tests/sample-config.json");

        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["method", "site"])->getMock();
        $request->expects($this->any())->method("method")->willReturn("post");
        $request->expects($this->any())->method("site")->willReturn("test");

        $route = new Route($request, "/realfile.txt", ["file" => "realfile.txt"], ["realfile.txt"]);
        $this->invokeMethod($route, "matchView");

        $this->assertEquals("./tests/sites/test/realfile.txt", $route->file());
    }

    public function testValidatingVariables() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["variables"])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(["key" => "value"]);

        $data = [
            "variables" => [
                "key" => [
                    "required" => true
                ]
            ]
        ];
        $route = new Route($request, "", $data, []);
        $this->invokeMethod($route, "validateVariables");
    }

    public function testValidatingUnsetVariables() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["variables"])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(["key" => "value"]);

        $data = [
            "variables" => [
                "name" => [
                    "required" => true
                ]
            ]
        ];

        $this->expectException("Penny\ResponseException");
        $route = new Route($request, "", $data, []);
        $this->invokeMethod($route, "validateVariables");
    }

    public function testValidatingUnsetVariablesWithCustomErrorMessage() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["variables"])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(["key" => "value"]);

        $data = [
            "variables" => [
                "name" => [
                    "required" => true,
                    "errors" => [
                        "missing" => "missing name variable"
                    ]
                ]
            ]
        ];

        $this->expectException("Penny\ResponseException");
        $route = new Route($request, "", $data, []);
        $this->invokeMethod($route, "validateVariables");
    }

    public function testValidatingUnsetVariablesWithCustomErrorDataNoCode() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["variables"])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(["key" => "value"]);

        $data = [
            "variables" => [
                "name" => [
                    "required" => true,
                    "errors" => [
                        "missing" => [
                            "message" => "missing name variable"
                        ]
                    ]
                ]
            ]
        ];

        $this->expectException("Penny\ResponseException");
        $route = new Route($request, "", $data, []);
        $this->invokeMethod($route, "validateVariables");
    }

    public function testValidatingUnsetVariablesWithCustomErrorDataWithCode() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(["variables"])->getMock();
        $request->expects($this->any())->method("variables")->willReturn(["key" => "value"]);

        $data = [
            "variables" => [
                "name" => [
                    "required" => true,
                    "errors" => [
                        "missing" => [
                            "message" => "missing name variable",
                            "code" => 100
                        ]
                    ]
                ]
            ]
        ];

        $this->expectException("Penny\ResponseException");
        $route = new Route($request, "", $data, []);
        $this->invokeMethod($route, "validateVariables");
    }
}

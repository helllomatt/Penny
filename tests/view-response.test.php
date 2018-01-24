<?php

use Penny\ViewResponse;
use Penny\Route;
use Penny\Request;
use Penny\Config;
use PHPUnit\Framework\TestCase;

class ViewResponseTest extends TestCase {
    public static function setUpBeforeClass() {
        Config::load('./tests/sample-config.json');
    }

    public function testCheckingViewFileNotDefined() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn([]);
        $vr = new ViewResponse($route, ["folder" => "test"]);
        $this->expectException("Penny\ResponseException");
        $vr->viewExists();
    }

    public function testCheckingViewFile() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["view" => "homepage.view.php"]);
        $vr = new ViewResponse($route, ["folder" => "test"]);

        $this->assertTrue($vr->viewExists());
    }

    public function testCheckingViewFileDoesntExist() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["view" => "no"]);
        $vr = new ViewResponse($route, ["folder" => "test", "theme" => ["folder" => "test"]]);

        $vr->viewExists();
        $this->assertEquals(404, $vr->errorCode());
    }

//    public function testCheckingNoViewDefined() {
//        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
//        $route->expects($this->any())->method("data")->willReturn([]);
//        $vr = new ViewResponse($route, []);
//
//        $this->expectException('Penny\ResponseException');
//        $vr->viewExists();
//    }
//
////    public function testNoExistingView() {
////        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
////        $request->expects($this->any())->method("site")->willReturn("defaultSite");
////        $route = new Route($request, '/', ['view' => 'asdf.php'], ['']);
////        $this->expectException('Penny\ResponseException');
////        $vr = new ViewResponse($route, Config::forSite($request->site()));
////    }
//
//    public function testNoThemeDefined() {
//        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
//        $request->expects($this->any())->method("site")->willReturn("defaultSite");
//        $route = new Route($request, '/', [], ['']);
//        $this->expectException('Penny\ResponseException');
//        $vr = new ViewResponse($route, ['folder' => 'default']);
//        $vr->getTheme();
//    }
//
//    public function testNoThemeExists() {
//        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
//        $request->expects($this->any())->method("site")->willReturn("defaultSite");
//        $route = new Route($request, '/', [], ['']);
//        $this->expectException('Penny\ResponseException');
//        $vr = new ViewResponse($route, ['folder' => 'default', 'theme' => 'asdf']);
//        $vr->getTheme();
//    }
//
//    // public function testResponse() {
//    //     $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
//    //     $request->expects($this->any())->method("site")->willReturn("defaultSite");
//    //     $route = new Route($request, '/', ['view' => 'homepage.view.php'], ['']);
//    //     $vr = new ViewResponse($route, Config::forSite($request->site()));
//    //
//    //     $this->expectOutputString('<html><body>\nhi!\n</body></html>');
//    //     $vr->respond();
//    // }
//
//    public function testGlobalScripts() {
//        $this->assertEquals("<script src='global/test.js'></script><script src='global/framework.js'></script>", ViewResponse::getGlobalScripts());
//    }
//
//    public function testGlobalStyles() {
//        $this->assertEquals("<link rel='stylesheet' type='text/css' href='global/test.css'>", ViewResponse::getGlobalStyles());
//    }
//
    public function testGettingDefaultTheme() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["theme" => "index.php", "view" => "homepage.view.php"]);

        $vr = new ViewResponse($route, ["folder" => "test", "theme" => ["folder" => "test"]]);
        $vr->getDefaultTheme();
        $this->assertEquals("./tests/themes/test/index.php", $vr->themeFile());
    }

    public function testGettingDefaultThemeNotDefined() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["theme" => "index.php", "view" => "homepage.view.php"]);

        $vr = new ViewResponse($route, ["folder" => "test"]);
        $this->expectException("Penny\ResponseException");
        $vr->getDefaultTheme();
    }

    public function testGettingDefaultThemeFileDoesntExist() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["theme" => "no", "view" => "homepage.view.php"]);

        $vr = new ViewResponse($route, ["folder" => "test", "theme" => ["folder" => "test"]]);
        $this->expectException("Penny\ResponseException");
        $vr->getDefaultTheme();
    }

    public function testGettingErrorTheme() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["view" => "homepage.view.php"]);

        $vr = new ViewResponse($route, ["folder" => "test", "theme" => ["folder" => "test"]]);

        $vr->getErrorTheme();
        $this->assertEquals("./tests/themes/test/error.php", $vr->errorFile());
    }

    public function testGettingErrorThemeNotDefined() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["view" => "homepage.view.php"]);

        $vr = new ViewResponse($route, ["folder" => "test"]);

        $this->expectException("Penny\ResponseException");
        $vr->getErrorTheme();
    }

    public function testGettingErrorThemeFileDoesntExist() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["view" => "homepage.view.php"]);

        $vr = new ViewResponse($route, ["folder" => "test", "theme" => ["folder" => "test"]]);
        $this->expectException("Penny\ResponseException");
        $vr->getErrorTheme("no");
    }

    public function testResponding() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["theme" => "blank.php", "view" => "homepage.view.php"]);

        $vr = new ViewResponse($route, ["folder" => "test", "theme" => ["folder" => "test"]]);
        $vr->getDefaultTheme();
        $this->expectOutputString("blank.php");
        $vr->respond();
    }

    public function testContents() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["view" => "homepage.view.php"]);
        $vr = new ViewResponse($route, ["folder" => "test"]);

        $vr->viewExists();
        $this->expectOutputString("hi!");
        $vr->contents();
    }

    public function testIncludingSiteFile() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn([]);
        $vr = new ViewResponse($route, ["folder" => "test"]);

        $this->expectOutputString("hi!");
        $vr->includeSiteFile("homepage.view.php");
    }

    public function testGettingRouteVariables() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["requestVars"])->getMock();
        $route->expects($this->any())->method("requestVars")->willReturn(["key" => "value"]);
        $vr = new ViewResponse($route, ["folder" => "test"]);

        $this->assertEquals("value", $vr->variable("key"));
    }

    public function testGettingRouteVariablesUndefined() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["requestVars"])->getMock();
        $route->expects($this->any())->method("requestVars")->willReturn([]);
        $vr = new ViewResponse($route, ["folder" => "test"]);

        $this->assertNull($vr->variable("no"));
    }

    public function testIncludingThemeFile() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["requestVars"])->getMock();
        $route->expects($this->any())->method("requestVars")->willReturn([]);
        $vr = new ViewResponse($route, ["folder" => "test", "theme" => ["folder" => "test"]]);

        $this->expectOutputString("blank.php");
        $vr->includeThemeFile("blank.php");
    }

    public function testIncludingThemeFileDoesntExist() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["requestVars"])->getMock();
        $route->expects($this->any())->method("requestVars")->willReturn([]);
        $vr = new ViewResponse($route, ["folder" => "test", "theme" => ["folder" => "test"]]);

        $this->expectException("Penny\ResponseException");
        $vr->includeThemeFile("no");
    }

    public function testGettingBaseHref() {
        $vr = new ViewResponse(null, ["folder" => "test", "domain" => "localhost/"]);
        $this->assertEquals("<base href='/'>", $vr->baseHref());
    }

    public function testGettingScripts() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["js" => ["script.js"]]);
        $vr = new ViewResponse($route, ["folder" => "test", "domain" => "localhost/"]);
        $this->assertEquals("<script type='text/javascript' src='script.js'></script>", $vr->getScripts());
    }

    public function testAddingAndGettingGlobalScripts() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["js" => ["/script.js"]]);
        $vr = new ViewResponse($route, ["folder" => "test", "domain" => "localhost/"]);
        $this->assertEquals("<script type='text/javascript' src='script.js'></script>", $vr->getScripts());
        ViewResponse::clearGlobalScripts();
    }

    public function testGettingThirdPartyScripts() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["js" => ["://script.js"]]);
        $vr = new ViewResponse($route, ["folder" => "test", "domain" => "localhost/"]);
        $this->assertEquals("<script type='text/javascript' src='://script.js'></script>", $vr->getScripts());
    }

    public function testGettingFolderScripts() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["js" => ["scripts/"]]);
        $vr = new ViewResponse($route, ["folder" => "test", "domain" => "localhost/"]);
        $this->assertEquals("<script type='text/javascript' src='scripts/script1.js'></script><script type='text/javascript' src='scripts/script2.js'></script>", $vr->getScripts());
    }

    public function testGettingStyles() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["css" => ["style.css"]]);
        $vr = new ViewResponse($route, ["folder" => "test", "domain" => "localhost/"]);
        $this->assertEquals("<link rel='stylesheet' type='text/css' href='global/test.css'><link rel='stylesheet' type='text/css' href='style.css'>", $vr->getStyles());
    }

    public function testGettingThirdPartyStyles() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["css" => ["://style.css"]]);
        $vr = new ViewResponse($route, ["folder" => "test", "domain" => "localhost/"]);
        $this->assertEquals("<link rel='stylesheet' type='text/css' href='global/test.css'><link rel='stylesheet' type='text/css' href='://style.css'>", $vr->getStyles());
    }

    public function testGettingStylesFromFolder() {
        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(["data"])->getMock();
        $route->expects($this->any())->method("data")->willReturn(["css" => ["styles/"]]);
        $vr = new ViewResponse($route, ["folder" => "test", "domain" => "localhost/"]);
        $this->assertEquals("<link rel='stylesheet' type='text/css' href='global/test.css'><link rel='stylesheet' type='text/css' href='styles/style1.css'><link rel='stylesheet' type='text/css' href='styles/style2.css'>", $vr->getStyles());
    }
}

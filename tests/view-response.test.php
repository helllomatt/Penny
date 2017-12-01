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

    public function testCheckingViewFile() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $route = new Route($request, '/', ['view' => 'homepage.view.php'], ['']);
        $vr = new ViewResponse($route, Config::forSite($request->site()));

        $this->assertTrue($vr->viewExists());
    }

    public function testCheckingNoViewDefined() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $route = new Route($request, '/', [], ['']);
        $this->expectException('Penny\ResponseException');
        $vr = new ViewResponse($route, Config::forSite($request->site()));
    }

    public function testNoExistingView() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $route = new Route($request, '/', ['view' => 'asdf.php'], ['']);
        $this->expectException('Penny\ResponseException');
        $vr = new ViewResponse($route, Config::forSite($request->site()));
    }

    public function testNoThemeDefined() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $route = new Route($request, '/', [], ['']);
        $this->expectException('Penny\ResponseException');
        $vr = new ViewResponse($route, ['folder' => 'default']);
        $vr->getTheme();
    }

    public function testNoThemeExists() {
        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
        $request->expects($this->any())->method("site")->willReturn("defaultSite");
        $route = new Route($request, '/', [], ['']);
        $this->expectException('Penny\ResponseException');
        $vr = new ViewResponse($route, ['folder' => 'default', 'theme' => 'asdf']);
        $vr->getTheme();
    }

    // public function testResponse() {
    //     $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods([])->getMock();
    //     $request->expects($this->any())->method("site")->willReturn("defaultSite");
    //     $route = new Route($request, '/', ['view' => 'homepage.view.php'], ['']);
    //     $vr = new ViewResponse($route, Config::forSite($request->site()));
    //
    //     $this->expectOutputString('<html><body>\nhi!\n</body></html>');
    //     $vr->respond();
    // }

    public function testGlobalScripts() {
        $this->assertEquals("<script src='global/test.js'></script><script src='global/framework.js'></script>", ViewResponse::getGlobalScripts());
    }

    public function testGlobalStyles() {
        $this->assertEquals("<link rel='stylesheet' type='text/css' href='global/test.css'>", ViewResponse::getGlobalStyles());
    }
}

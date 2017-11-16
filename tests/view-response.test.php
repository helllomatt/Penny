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
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/', ['view' => 'homepage.view.php'], ['']);
        $vr = new ViewResponse($route, Config::forSite($request->site()));

        $this->assertTrue($vr->viewExists());
    }

    public function testCheckingNoViewDefined() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/', [], ['']);
        $this->expectException('Penny\ResponseException');
        $vr = new ViewResponse($route, Config::forSite($request->site()));
    }

    public function testNoExistingView() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/', ['view' => 'asdf.php'], ['']);
        $this->expectException('Penny\ResponseException');
        $vr = new ViewResponse($route, Config::forSite($request->site()));
    }

    public function testNoThemeDefined() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/', [], ['']);
        $this->expectException('Penny\ResponseException');
        $vr = new ViewResponse($route, ['folder' => 'default']);
        $vr->getTheme();
    }

    public function testNoThemeExists() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/', [], ['']);
        $this->expectException('Penny\ResponseException');
        $vr = new ViewResponse($route, ['folder' => 'default', 'theme' => 'asdf']);
        $vr->getTheme();
    }

    public function testResponse() {
        $request = (new Request(['pennySite' => 'defaultSite']))->overrideMethod('get')->findSite();
        $route = new Route($request, '/', ['view' => 'homepage.view.php'], ['']);
        $vr = new ViewResponse($route, Config::forSite($request->site()));

        $this->expectOutputString('<html><body>hi!'.PHP_EOL.'</body></html>'.PHP_EOL);
        $vr->respond();
    }
}

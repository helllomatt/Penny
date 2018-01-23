<?php

use Penny\Request;
use Penny\Config;
use PHPUnit\Framework\TestCase;

class RequestTests extends TestCase {
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

    public function testRequestWithoutSite() {
        $this->expectException('Penny\RequestException');
        (new Request())->overrideMethod('get')->findSite();
    }

    public function testFindingMethod() {
        $request = new Request([]);
        $this->assertEquals('cli', $request->method());
    }

    public function testFindingVariables() {
        $request = new Request(['abc', 'def']);
        $this->assertEquals(['abc', 'def'], $request->variables());
    }

    public function testGettingSite() {
        Config::load('./tests/sample-config.json');
        $request = (new Request(['pennyRoute' => 'test']))->overrideMethod('get')->setDomain("localhost/test")->findSite();
        $this->assertEquals('defaultSite', $request->site());
    }

    public function testRealFile() {
        Config::load('./tests/sample-config.json');
        $request = (new Request(['pennyRoute' => 'realfile.txt']))
            ->overrideMethod('get')->setDomain("localhost/test")->findSite();

        $request->checkRealFile();

        $this->assertTrue($request->forFile());
    }

    public function testNoRedirectSlash() {
        $request = new Request(["pennyRoute" => "/"]);
        $redirect = $this->invokeMethod($request, "redirectSlash", [true]);
        $this->assertFalse($redirect);
    }

    public function testRedirectSlash() {
        $request = new Request(["pennyRoute" => "/hello/world/"]);
        $redirect = $this->invokeMethod($request, "redirectSlash", [true]);
        $this->assertTrue($redirect);
    }

    public function testSettingType() {
        $request = new Request();
        $request->setType("test");

        $this->assertEquals("test", $request->type());
    }

    public function testGettingVariables() {
        $request = new Request();
        $this->invokeMethod($request, "getHttpVariables");
        $this->assertEquals([], $request->variables());
    }

    public function testSettingManyVariables() {
        $request = new Request();
        $request->addVariables(["key1" => "value1", "key2" => "value2"]);
        $this->assertEquals(["key1" => "value1", "key2" => "value2"], $request->variables());
    }

    public function testSettingSingleVariable() {
        $request = new Request();
        $request->setVariable("key", "value");
        $this->assertEquals(["key" => "value"], $request->variables());
    }

    public function testGettingSingleVariable() {
        $request = new Request();
        $request->setVariable("key", "value");
        $this->assertEquals("value", $request->variable("key"));
    }

    public function testSettingBlankDomain() {
        $request = new Request();
        $this->expectException("Penny\RequestException");
        $request->setDomain();
    }

    public function testAllowedToServe() {
        $request = new Request();
        $this->assertTrue($request->allowedToServe());
    }

    public function testFindingNonCliMethod() {
        $request = new Request();
        $this->invokeMethod($request, "findMethod", ["get"]);
        $this->assertEquals("get", $request->method());
    }

    public function testNonCliRequest() {
        // there are no assertions here, just checking that there are no exceptions thrown
        $request = (new Request(['pennyRoute' => 'test']))->overrideMethod('get')->setDomain("localhost/test")->findSite();
        $request->nonCliRequest();
    }

    public function testFindingHttpVariables() {
        $request = new Request();
        $request->overrideMethod("get");
        $this->invokeMethod($request, "findVariables");
        $this->assertEquals([], $request->variables());
    }

    public function testGettingDomain() {
        $request = new Request();
        $request->getDomain("localhost", "asdf");
        $this->assertEquals("localhost/asdf/", $request->getDomain());
    }

    public function testGettingAllSites() {
        $sites = ["site1" => ["domain" => "short/"], "site2" => ["domain" => "longer/"]];
        $expected = ["site2" => ["domain" => "longer/"], "site1" => ["domain" => "short/"]];

        $request = new Request();
        $actual = $this->invokeMethod($request, "getAllSitesFromConfig", [$sites]);

        $this->assertEquals($expected, $actual);
    }

    public function testFindingBadSiteActualFile() {
        (new Request(['pennyRoute' => '/file1.txt']))->overrideMethod('get')->setDomain("localhost/")->findSite();
    }

    public function testFindingBadSite() {
        $this->expectException("Penny\RequestException");
        (new Request(['pennyRoute' => '/']))->overrideMethod('get')->setDomain("localhost/asdf")->findSite();
    }

    public function testCheckingRealFileNoRoute() {
        $this->expectException("Penny\RequestException");
        (new Request([]))->checkRealFile(true);
    }

    public function testCheckingRealFileMultipleGlobalFolders() {
        Config::load("tests/sample-config.2.json");
        $request = (new Request(['pennyRoute' => '/file1.txt']));
        $request->overrideMethod('get')->setDomain("localhost/")->setSite("defaultSite");
        $actual = $request->checkRealFile(true);
        $this->assertTrue($actual);
        Config::load("tests/sample-config.json");
    }

    public function testCheckingRealFileForTheme() {
        $request = (new Request(['pennyRoute' => '/theme/file.txt']));
        $request->overrideMethod('get')->setDomain("localhost/test/")->setSite("defaultSite");
        $actual = $request->checkRealFile(true);
        $this->assertTrue($actual);
    }

    public function testCheckingRealUnpermittedFile() {
        $request = (new Request(['pennyRoute' => '/homepage.view.php']));
        $request->overrideMethod('get')->setDomain("localhost/test/")->setSite("defaultSite");
        $actual = $request->checkRealFile(true);
        $this->assertFalse($actual);
    }

    public function testCheckingRealUnpermittedFileNonReturn() {
        $request = (new Request(['pennyRoute' => '/homepage.view.php']));
        $request->overrideMethod('get')->setDomain("localhost/test/")->setSite("defaultSite");
        $request->checkRealFile();
    }

    public function testGettingFoundFile() {
        $request = (new Request(['pennyRoute' => '/file1.txt']));
        $request->overrideMethod('get')->setDomain("localhost/")->setSite("defaultSite");
        $request->checkRealFile();
        $this->assertEquals("./tests/fs/file1.txt", $request->file());
    }
}

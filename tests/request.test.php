<?php

use Penny\Request;
use Penny\Config;
use PHPUnit\Framework\TestCase;

class RequestTests extends TestCase {
    public function testRequestWithoutSite() {
        $this->expectException('Penny\RequestException');
        $request = (new Request())->overrideMethod('get')->findSite();
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
}

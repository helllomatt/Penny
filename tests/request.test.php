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
        $request = new Request(['pennySite' => 'test']);
        $this->assertEquals('cli', $request->method());
    }

    public function testFindingVariables() {
        $request = new Request(['abc', 'def', 'pennySite' => 'test']);
        $this->assertEquals(['abc', 'def', 'pennySite' => 'test'], $request->variables());
    }

    public function testGettingSite() {
        $request = (new Request(['pennySite' => 'test']))->overrideMethod('get')->findSite();
        $this->assertEquals('test', $request->site());
    }

    public function testRealFile() {
        Config::load('./tests/sample-config.json');
        $request = (new Request(['pennySite' => 'defaultSite', 'pennyRoute' => 'realfile.txt']))
            ->overrideMethod('get')->findSite();

        $request->checkRealFile();

        $this->assertTrue($request->forFile());
    }
}

<?php

use Penny\Config;
use PHPUnit\Framework\TestCase;

class ConfigTests extends TestCase {
    public function testErrorWhenLoadingConfig() {
        $this->expectException('Penny\ConfigException');
        Config::load();
    }

    public function testTryingToGetNoConfig() {
        $this->expectException('Penny\ConfigException');
        Config::forSite();
    }

    public function testLoadingConfig() {
        Config::load('./tests/sample-config.json');
        $this->assertEquals(['test' => true], Config::forSite('testSite'));
    }

    public function testLoadingConfigForInvalidSite() {
        Config::load('./tests/sample-config.json');
        $this->expectException('Penny\ConfigException');
        Config::forSite('null');
    }

    public function testGettingValue() {
        Config::load('./tests/sample-config.json');
        $this->assertEquals('api', Config::get('apiIdentity'));
    }

    public function testGettingNonExistantValue() {
        Config::load('./tests/sample-config.json');
        $this->expectException('Penny\ConfigException');
        Config::get('asdf');
    }

    public function testConfigKeyExists() {
        Config::load('./tests/sample-config.json');
        $this->assertTrue(Config::hasValue('apiIdentity'));
    }
}

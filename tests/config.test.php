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

    public function testAddingSingleKeyValue() {
        Config::add("key", "value");
        $this->assertEquals("value", Config::get("key"));
    }

    public function testLoadingNonJsonFile() {
        $this->expectException("Penny\ConfigException");
        Config::load("tests/fs/file1.txt");
    }

    public function testUnloadingConfigAndGettingSiteData() {
        Config::unload();
        $this->assertFalse(Config::loaded());
        $this->expectException("Penny\ConfigException");
        Config::forSite();
    }

    public function testUnloadingAndCheckingTheValueOfSomething() {
        Config::unload();
        $this->assertFalse(Config::loaded());
        $this->expectException("Penny\ConfigException");
        $this->assertFalse(Config::hasValue("no"));
    }

    public function testUnloadingAndGettingSiteRootFolder() {
        Config::unload();
        $this->assertFalse(Config::loaded());
        $this->expectException("Penny\ConfigException");
        $this->assertFalse(Config::siteRootFolder());
    }

    public function testUnloadingAndGettingApiRootFolder() {
        Config::unload();
        $this->assertFalse(Config::loaded());
        $this->expectException("Penny\ConfigException");
        $this->assertFalse(Config::apiFolder());
    }

    public function testUnloadingAndGettingThemeRootFolder() {
        Config::unload();
        $this->assertFalse(Config::loaded());
        $this->expectException("Penny\ConfigException");
        $this->assertFalse(Config::themeFolder());
    }

    public function testUnloadingAndGettingTheSiteFolder() {
        Config::unload();
        $this->assertFalse(Config::loaded());
        $this->expectException("Penny\ConfigException");
        $this->assertFalse(Config::siteFolder());
    }

    public function testUnloadingConfigAndGettingSomething() {
        Config::unload();
        $this->assertFalse(Config::loaded());
        $this->expectException("Penny\ConfigException");
        Config::get();
        Config::load("./tests/sample-config.json"); //reload
    }

    public function testGettingSomethingThatDoesntExist() {
        Config::load("./tests/sample-config.json"); //reload
        $this->assertNull(Config::get("hello", true));
    }

    public function testGettingSiteFolderNotProvided() {
        $this->expectException("Penny\ConfigException");
        Config::siteFolder();
    }

    public function testGettingSiteFolderNotExisting() {
        Config::delete("siteRootFolder");
        $this->assertEquals("sites/default", Config::siteFolder("default"));
        Config::add("siteRootFolder", "sites");
    }

    public function testGettingDefaultSiteRootFolder() {
        Config::delete("siteRootFolder");
        $this->assertEquals("sites/", Config::siteRootFolder());
    }

    public function testGettingSiteRootFolder() {
        Config::add("siteRootFolder", "asdf");
        $this->assertEquals("asdf/", Config::siteRootFolder());
        Config::add("siteRootFolder", "sites");
    }

    public function testGettingDefaultApiFolder() {
        Config::delete("apiRootFolder");
        $this->assertEquals("apis/", Config::apiFolder());
    }

    public function testGettingApiFolder() {
        Config::add("apiRootFolder", "asdf");
        $this->assertEquals("asdf/", Config::apiFolder());
        Config::add("apiRootFolder", "apis");
    }

    public function testGettingDefaultThemeFolder() {
        Config::delete("themeRootFolder");
        $this->assertEquals("themes/", Config::themeFolder());
    }
}

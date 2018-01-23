<?php

use Penny\JSON;
use PHPUnit\Framework\TestCase;

class JSONTest extends TestCase {
    public static function setUpBeforeClass() {
        JSON::add('key', 'val');
    }

    public function testGettingRawData() {
        $this->assertEquals(['key' => 'val'], JSON::getRaw());
    }

    public function testGettingJSON() {
        $this->assertEquals('{"key":"val"}', JSON::get());
    }

    public function testAddingData() {
        JSON::add("key", "value");
        $this->assertEquals(["key" => "value"], JSON::getRaw());
    }

    public function testAddingAndClearingData() {
        JSON::add("key", "value");
        $this->assertEquals(["key" => "value"], JSON::getRaw());
        JSON::clear();
        $this->assertEquals([], JSON::getRaw());
    }
}

<?php

use Penny\JSON;
use PHPUnit\Framework\TestCase;

class JSONTest extends TestCase {
    public static function setUpBeforeClass() {
        JSON::add('key', 'val');
    }
    
    public function testAddingData() {
        $this->assertEquals(['key' => 'val'], JSON::getRaw());
    }

    public function testGettingJSON() {
        $this->assertEquals('{"key":"val"}', JSON::get());
    }
}

<?php

namespace Penny;

use PHPUnit\Framework\TestCase;

class MiscTests extends TestCase {
    public function testPre() {
        $this->expectOutputString("<pre>hi</pre>", pre("hi"));
    }

    public function testEncodingId() {
        $this->assertEquals("jmeraejblpv", encode_id(1, "abcdefghijklmnopqrstuvwxyz"));
    }

    public function testDecodingId() {
        $this->assertEquals(1, decode_id("jmeraejblpv", true, "abcdefghijklmnopqrstuvwxyz"));
    }

    public function testAutoloading() {
        autoload("Test", "./tests/includes");
        $this->assertTrue(class_exists("Test\Greeting"));
    }
}
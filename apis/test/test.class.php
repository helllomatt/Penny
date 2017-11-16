<?php

namespace Test;

use Penny\JSON;

class Greeting {
    public static function say_hello() {
        JSON::add('greeting', 'Hello');
    }

    public static function sayHelloToName($name, $age = null) {
        $greeting = 'Hello, '.$name.'!';
        if ($age !== null) {
            $greeting .= ' You\'re '.$age.' years old!';
        }
        return $greeting;
    }

    public static function sayHelloToName_ECHO($name, $age = null) {
        echo static::sayHelloToName($name, $age);
    }

    public static function sayHelloToName_JSON($name, $age = null) {
        JSON::add("greeting", static::sayHelloToName($name, $age));
    }
}

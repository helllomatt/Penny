<?php

namespace Test;

class Greeting {
    public static function say_hello() {
        return true;
    }

    public static function echo_hello() {
        echo "Hello, World!";
    }

    public static function echo_hello_name($name) {
        echo "Hello, ".$name."!";
    }

    public static function return_404() {
        return 404;
    }
}

<?php

namespace Penny;

class JSON {
    private static $raw_data = [];

    /**
     * Adds a key=>value pair to the data
     *
     * @param string $key
     * @param any $value
     */
    public static function add($key, $value) {
        static::$raw_data[$key] = $value;
    }

    /**
     * Returns all of the collected information as JSON
     *
     * @return string
     */
    public static function get() {
        return json_encode(static::$raw_data);
    }

    /**
     * Returns all of the collected information
     *
     * @return array
     */
    public static function getRaw() {
        return static::$raw_data;
    }

    /**
     * Clears the data
     *
     * @return void
     */
    public static function clear() {
        static::$raw_data = [];
    }
}

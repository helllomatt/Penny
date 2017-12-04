<?php

namespace Penny;

use Hashids\Hashids;


/**
 * Checks if a string is JSON
 *
 * @param  string  $string
 * @return boolean
 */
function isJSON($string) {
    json_decode($string);
    return (json_last_error() == JSON_ERROR_NONE);
}


/**
 * Autoloads a class
 *
 * @param  string $prefix
 * @param  string $base_dir
 * @return none
 */
function autoload($prefix, $base_dir) {
    $base_dir = clean_slashes($base_dir);
    spl_autoload_register(function ($class) use ($prefix, $base_dir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) return;

        $files = FileSystem::scan($base_dir, ['recursive' => true, 'flat' => true]);

        foreach ($files as $file) {
            if (FileSystem::getExtension($file) !== 'php') continue;
            if (trim(FileSystem::findNamespace($base_dir.'/'.$file)).'\\' == $prefix) {
                require_once $base_dir.'/'.$file;
            }
        }
    });
}

/**
 * When working with a URL that has multiple slashes next to eachother, replaces
 * all of them with just one instead
 *
 * Example:
 * From: localhost/website//path///hello
 * To:   localhost/website/path/hello
 *
 * @param  string $url
 * @return string formatted url
 */
function clean_slashes($url) {
    return preg_replace('/\/+/', '/', $url);
}


function pre($array) {
    echo "<pre>".print_r($array, true)."</pre>";
}

function decode_id($id, $allow_numeric = true) {
    if (is_numeric($id) && $allow_numeric) return $id;
    $hashids = new Hashids("", 11, Config::get("hashidSalt"));
    $decoded = $hashids->decode($id);
    if (empty($decoded)) return 0;
    return $decoded[0];
}

function encode_id($id) {
    $hashids = new Hashids("", 11, Config::get("hashidSalt"));
    return $hashids->encode($id);
}

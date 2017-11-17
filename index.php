<?php

/**
 * Penny.
 *
 * API, CMS, Framework.
 */

namespace Penny;

if (!defined('REL_ROOT')) define('REL_ROOT', './vendor/helllomatt/penny/');
if (!defined('SITE')) define('SITE', '');

require 'vendor/autoload.php';

try {
    if (!isset($argv)) $argv = [];
    $test_command = $argv[1] == "test-command" ? true : false;
    if ($test_command) array_splice($argv, 1, 1);

    Config::load(REL_ROOT.'config.json');

    $request = new Request($argv, SITE);

    $router = new Router($request);
    $route = $router->getMatch();

    if ($route === null && $request->method() == 'cli') {
        echo 'Command not found.'.PHP_EOL;
    } elseif ($request->method() == 'cli') {
        if (!$test_command) {
            $opts = new CliOpts($request, $route);
            $api_response = new ApiResponse($route, []);
            $api_response->respond($opts->options(), false);
        }
    } else {
        echo 'UNKNOWN RESPONSE, THROW AN EXCEPTION LIKE CANDY ON HALLOWEEN.';
    }
} catch (ConfigException $e) {
    print_r('CONFIG EXCEPTION: '.$e->getMessage());
} catch (CliOptException $e) {
    print_r($e->getMessage());
} catch (RequestException $e) {
    print_r('REQUEST EXCEPTION: '.$e->getMessage());
} catch (RouterException $e) {
    print_r('ROUTER EXCEPTION: '.$e->getMessage());
} catch (ResponseException $e) {
    print_r('RESPONSE EXCEPTION: '.$e->getMessage());
} catch (Exception $e) {
    print_r('DEFAULT EXCEPTION: '.$e->getMessage());
}

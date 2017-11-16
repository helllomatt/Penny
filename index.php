<?php

/**
 * Penny.
 *
 * API, CMS, Framework.
 */

namespace Penny;

if (!defined('REL_ROOT')) define('REL_ROOT', './');
if (!defined('SITE')) define('SITE', '');

require 'vendor/autoload.php';

try {
    if (!isset($argv)) $argv = [];
    Config::load(REL_ROOT.'config.json');

    $request = new Request($argv, SITE);

    if (($request->forFile() && $request->allowedToServe())) {
        header('Content-Type: '.$request->fileType());
        header('Content-Length: '.filesize($request->file()));
        readfile($request->file());
    } elseif ($request->forFile() && !$request->allowedToServe()) {
        $view_response = new ViewResponse(null, Config::forSite($request->site()), true);
        $view_response->error(404);
    } else {
        $router = new Router($request);
        $route = $router->getMatch();

        if ($route === null && $request->method() == 'cli') {
            echo 'Command not found.'.PHP_EOL;
        } elseif ($route === null && $request->type() == 'cli') {
            http_response_code(404);
        } elseif ($route === null && $request->type() == 'view') {
            $view_response = new ViewResponse(null, Config::forSite($request->site()), true);
            $view_response->error(404);
        } elseif ($request->method() == 'cli') {
            $opts = new CliOpts($request, $route);
            $api_response = new ApiResponse($route, []);
            $api_response->respond($opts->options(), false);
        } elseif ($route === null && $request->type() == 'api') {
            $api_response = new ApiResponse(null, Config::forSite($request->site()));
            $api_response->error(404);
        } elseif ($request->type() == 'api') {
            header('Content-Type: application/json');
            try {
                $api_response = new ApiResponse($route, Config::forSite($request->site()), $request);
                $api_response->respond($api_response->variables());
            } catch (\Exception $e) {
                JSON::clear();
                JSON::add('error', true);
                JSON::add('message', $e->getMessage());
                JSON::add('code', $e->getCode());
                echo JSON::get();
            }
        } elseif ($request->type() == 'view' && $route->forFile()) {
            header('Content-Type: '.mime_content_type($route->file()));
            header('Content-Length: '.filesize($route->file()));
            readfile($route->file());
        } elseif ($request->type() == 'view') {
            $view_response = new ViewResponse($route, Config::forSite($request->site()));
            $view_response->respond();
        } else {
            echo 'UNKNOWN RESPONSE, THROW AN EXCEPTION LIKE CANDY ON HALLOWEEN.';
        }
    }
} catch (ConfigException $e) {
    print_r('CONFIG EXCEPTION: '.$e->getMessage());
    print_r($e->getTrace());
} catch (CliOptException $e) {
    print_r($e->getMessage());
    //print_r($e->getTrace());
} catch (RequestException $e) {
    print_r('REQUEST EXCEPTION: '.$e->getMessage());
    print_r($e->getTrace());
} catch (RouterException $e) {
    print_r('ROUTER EXCEPTION: '.$e->getMessage());
    print_r($e->getTrace());
} catch (ResponseException $e) {
    print_r('RESPONSE EXCEPTION: '.$e->getMessage());
    print_r($e->getTrace());
} catch (Exception $e) {
    print_r('DEFAULT EXCEPTION: '.$e->getMessage());
    print_r($e->getTrace());
}

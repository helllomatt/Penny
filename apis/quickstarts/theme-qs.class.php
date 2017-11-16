<?php

namespace PennyCLI;

use Penny\CliOpts;
use ZipArchive;

class ThemeQuickstart {
    private static $url = "https://git.helllomatt.com/matt/penny-theme-quickstart/archive/master.zip";

    public static function download() {
        CliOpts::out("Downloading theme shell...");
        $zip = file_get_contents(static::$url, false, stream_context_create(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]));
        $tempfile = file_put_contents(REL_ROOT."temp/theme.zip", $zip);
    }

    public static function cleanup() {
        CliOpts::out("Cleaning up...");
        $path = REL_ROOT."/temp/theme.zip";
        if (file_exists($path)) unlink($path);
    }

    public static function new_theme($first = false) {
        if ($first) {
            $themename = CliOpts::readline("What is the name of your first theme? (no spaces) ");
        } else {
            $themename = CliOpts::readline("What is the name of your theme? (no spaces) ");
        }

        $config = json_decode(file_get_contents("./config.json"), true);
        $root = isset($config['themeRootFolder']) ? $config['themeRootFolder'] : "themes";

        if (is_dir($root."/".$themename)) {
            CliOpts::out("Theme already exists.");
            return;
        }

        static::download();
        CliOpts::out("Extracting...");
        $zip = new ZipArchive();
        $res = $zip->open(REL_ROOT."/temp/theme.zip");
        if ($res === true) {
            $zip->extractTo($root);
            $zip->close();
            rename($root."/penny-theme-quickstart", $root."/".$themename);
        }

        CliOpts::out("Updating config...");

        static::cleanup();
        CliOpts::out("Done");
        return $themename;
    }
}

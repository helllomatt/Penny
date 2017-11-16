<?php

namespace PennyCLI;

use Penny\CliOpts;
use ZipArchive;

class SiteQuickstart {
    private static $url = "https://git.helllomatt.com/matt/penny-site-quickstart/archive/master.zip";

    public static function download() {
        CliOpts::out("Downloading site shell...");
        $zip = file_get_contents(static::$url, false, stream_context_create(["ssl" => ["verify_peer" => false, "verify_peer_name" => false]]));
        $tempfile = file_put_contents(REL_ROOT."temp/site.zip", $zip);
    }

    public static function cleanup() {
        CliOpts::out("Cleaning up...");
        $path = REL_ROOT."/temp/site.zip";
        if (file_exists($path)) unlink($path);
    }

    public static function new_site($first = false, $theme = null) {
        if ($first) {
            $sitename = CliOpts::readline("What is the name of your first website? (no spaces) ");
        } else {
            $sitename = CliOpts::readline("What is the name of your website? (no spaces) ");
        }
        $domain = CliOpts::readline("What URL should your website respond to? (eg: localhost/".$sitename.") ");
        if ($theme == null) {
            $theme = CliOpts::readline("What theme are you going to use? ");
        }

        $config = json_decode(file_get_contents("./config.json"), true);
        $root = isset($config['siteRootFolder']) ? $config['siteRootFolder'] : "sites";

        if (is_dir($root."/".$sitename)) {
            CliOpts::out("Site already exists.");
            return;
        }

        static::download();
        CliOpts::out("Extracting...");
        $zip = new ZipArchive();
        $res = $zip->open(REL_ROOT."/temp/site.zip");
        if ($res === true) {
            $zip->extractTo($root);
            $zip->close();
            rename($root."/penny-site-quickstart", $root."/".$sitename);
        }

        $config[$sitename] = [
            "folder" => $sitename,
            "theme" => [ "folder" => $theme ],
            "domain" => $domain
        ];

        CliOpts::out("Updating config...");
        file_put_contents("./config.json", json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        static::cleanup();
        CliOpts::out("Done");
    }
}

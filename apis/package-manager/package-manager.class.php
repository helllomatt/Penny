<?php

namespace PennyCLI;

use PennyPackage\Package;
use PennyPackage\PackageException;
use Penny\CliOpts;

class PackageManager {
    public static function install($link, $name) {
        CliOpts::out("Link: ".$link);
        CliOpts::out("Name: ".$name);

        try {
            $package = new Package($link, $name);
            // CliOpts::out($package->tar());

            CliOpts::out("Downloading...");
            $package->download();

            CliOpts::out("Extracting...");
            $package->extract();

            CliOpts::out("Gathering information...");
            $package->readConfig();

            if ($package->hasAPI()) {
                CliOpts::out("Moving API files...");
                $package->moveAPI();
            }

            if ($package->hasSite()) {
                CliOpts::out("Moving site files...");
                $package->moveSite();
            }

            CliOpts::out("Cleaning up...");
            $package->cleanUp();
        } catch (PackageException $e) {
            CliOpts::out("FAIL: ".$e->getMessage());
        }
    }
}

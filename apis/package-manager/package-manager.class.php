<?php

namespace PennyCLI;

use PennyPackage\Package;
use PennyPackage\PackageException;
use Penny\CliOpts;

class PackageManager {
    public static function install($link, $name) {
        try {
            $package = new Package($link, $name);

            CliOpts::out("Downloading...");
            $package->download();

            CliOpts::out("Extracting...");
            $package->extract();

            CliOpts::out("Gathering information...");
            $package->readConfig();

            if ($package->hasAPI()) {
                CliOpts::out("Installing API files...");
                $package->moveAPI();
            }

            if ($package->hasSite()) {
                CliOpts::out("Installing site files...");
                $package->moveSite();
            }

            CliOpts::out("Cleaning up...");
            $package->cleanUp();
        } catch (PackageException $e) {
            CliOpts::out("FAIL: ".$e->getMessage());
        }
    }

    public static function update($link, $name) {
        try {
            $package = new Package($link, $name);

            CliOpts::out("Downloading...");
            $package->download();

            CliOpts::out("Extracting...");
            $package->extract();

            CliOpts::out("Gathering information...");
            $package->readConfig();

            if ($package->hasAPI()) {
                CliOpts::out("Updating API files...");
                $package->moveAPI(true);
            }

            if ($package->hasSite()) {
                CliOpts::out("Updating site files...");
                $package->moveSite();
            }

            CliOpts::out("Cleaning up...");
            $package->cleanUp();
        } catch (PackageException $e) {
            CliOpts::out("FAIL: ".$e->getMessage());
        }
    }

    public static function delete() {
        CliOpts::out("I don't know where the files are anymore since they aren't kept track of after installing.");
        CliOpts::out("You're on your own! Good luck bud!");
    }
}

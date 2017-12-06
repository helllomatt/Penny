<?php

namespace PennyPackage;

use Penny\CliOpts;
use Penny\FileSystem;
use Penny\Config;
use PharData;

class Package {
    private $_link;
    private $_name;
    private $_config = [];
    private $temp_folder = "temp-package/";
    private $_api_installed = false;
    private $_site_installed = false;

    public function __construct($link, $name) {
        $this->_link = $link;
        $this->_name = $name;
        mkdir($this->temp_folder);
    }

    public function tar() {
        return $this->_link."/archive/master.tar.gz";
    }

    public function name($replace_slashes = false, $last = false) {
        $name = $this->_name;

        if ($last) {
            $parts = explode("/", $name);
            $name = end($parts);
        }
        if ($replace_slashes) return str_replace("/", "-", $name);
        return $name;
    }

    public function download() {
        $read_handle = fopen($this->tar(), "rb", false, stream_context_create([
            "ssl" => [ "verify_peer" => false, "verify_peer_name" => false ]
        ]));

        if ($read_handle) {
            $write_handle = fopen($this->temp_folder.$this->name(true).".tar.gz", "wb");
            if ($write_handle) {
                while (!feof($read_handle)) {
                    fwrite($write_handle, fread($read_handle, 1024 * 8), 1024 * 8);
                }

                fclose($write_handle);
            }
            fclose($read_handle);
        }
    }

    public function extract() {
        $archive = new PharData($this->temp_folder.$this->name(true).".tar.gz");
        $archive->extractTo("./".$this->temp_folder);
    }

    public function readConfig() {
        $config_path = $this->temp_folder.$this->name(false, true)."/penny.json";
        if (!file_exists($config_path)) {
            $this->cleanUp();
            throw new PackageException("Configuration file doesn't exist where it should. Aborting.");
        }

        $this->_config = json_decode(file_get_contents($config_path), true);
    }

    public function hasAPI() {
        return isset($this->_config['api']) && is_dir($this->temp_folder.$this->name(false, true)."/".$this->_config['api']);
    }

    public function moveAPI() {
        $temp_path = $this->temp_folder.$this->name(false, true)."/".$this->_config['api'];
        $new_path = Config::apiFolder()."/".$this->name(false, true);

        if (is_dir($new_path)) {
            $this->cleanUp();
            throw new PackageException("An API already is installed there. Did you mean update-package?");
        }

        if (!FileSystem::copy($temp_path, $new_path)) {
            $this->cleanUp();
            throw new PackageException("Failed to move the API folder. Aborting.");
        }

        $this->_api_installed = true;
    }

    public function hasSite() {
        return isset($this->_config['site']) && is_dir($this->temp_folder.$this->name(false, true)."/".$this->_config['site']);
    }

    public function moveSite() {
        $temp_path = $this->temp_folder.$this->name(false, true)."/".$this->_config['site'];
        $new_path = Config::siteRootFolder()."/".$this->_config['site'];

        if (!FileSystem::copy($temp_path, $new_path)) {
            $this->cleanUp();
            throw new PackageException("Failed to move the site folder. Aborting.");
        }

        $this->_site_installed = true;
    }

    public function cleanUp($error = false) {
        $files = array_reverse(FileSystem::scan($this->temp_folder, ["flat" => true, "recursive" => true]));
        foreach ($files as $file) {
            $path = $this->temp_folder.$file;
            if (file_exists($path) && !is_dir($path)) {
                unlink($path);
            }
        }

        foreach ($files as $folder) {
            if (file_exists($folder) && is_dir($folder)) {
                rmdir($folder);
            }
        }

        if ($error) {

        }

        rmdir($this->temp_folder);
    }
}

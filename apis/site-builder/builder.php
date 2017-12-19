<?php

namespace SiteBuilder;

use Penny\Config;
use DomDocument;

class Builder {
    private $_name = "";
    private $_site_path = "";
    private $_config = [];
    private $_dist_config = [];
    private $_scripts = [];

    public function __construct($name) {
        $this->_name = $name;
        $this->_site_path = Config::siteFolder($this->name());

        $this->get_site_config();
        $this->setup_dist_folder();
        $this->get_global_scripts();
        $this->parse_routes();
        $this->make_script();
        $this->write_config();
    }

    public function name() {
        return $this->_name;
    }

    public function config() {
        return $this->_config;
    }

    public function dist_config() {
        return $this->_dist_config;
    }

    public function site_path() {
        return $this->_site_path;
    }

    public function scripts() {
        return $this->_scripts;
    }

    /**
     * Gets the configuration information for the site
     *
     * @return void
     */
    public function get_site_config() {
        $config_path = $this->site_path()."/config.json";
        $raw_config = file_get_contents($config_path); // check for existance first
        $this->_config = json_decode($raw_config, true);
        $this->_dist_config = $this->_config;
    }

    /**
     * Creates a dist folder within the site
     *
     * @return void
     */
    public function setup_dist_folder() {
        $dist_path = $this->site_path()."/dist";
        if (file_exists($dist_path)) {
            return; // delete folder and it's contents
        }
        mkdir($dist_path);
    }

    /**
     * Pops the scripts out of the files, just leaving the content.
     *
     * @return void
     */
    public function parse_routes() {
        $regex = "/\<script(.*?)?\>(.|\\n)*?\<\/script\>/i";
        $id = 0;

        foreach ($this->config()['routes'] as $path => $route) {
            $scripts = [];
            $view_path = $this->site_path()."/".$route['view'];
            $raw = file_get_contents($view_path);
            $parsed = $raw;
            $dom = new DomDocument();
            @$dom->loadHTML($raw);

            $route_scripts = $dom->getElementsByTagName("script");
            for ($i = 0; $i < $route_scripts->length; $i++) {
                $temp = new DOMDocument();
                $temp->appendChild($temp->importNode($route_scripts->item($i), true));
                $outer = $temp->saveHTML();
                $scripts[] = $route_scripts->item($i)->nodeValue;
                $parsed = str_replace(stripslashes($outer), "", stripslashes($parsed));
            }

            if (!empty($scripts)) {
                $this->add_script("var page".$id." = function() { ".implode("", $scripts)."};");
                $this->_dist_config['routes'][$path]['js'][] = "dist/site.js";
            }
            $this->_dist_config['routes'][$path]['vars']['page_id'] = $id;
            $this->_dist_config['routes'][$path]['view'] = "dist/".$this->_dist_config['routes'][$path]['view'];
            $this->make_view($route['view'], $parsed);
            $id++;
        }
    }

    /**
     * Adds a script to be compressed down
     *
     * @param string $script
     */
    public function add_script($script) {
        $this->_scripts[] = $script;
    }

    /**
     * Writes the dist view file
     *
     * @param  string  $file
     * @param  string  $content
     * @return void
     */
    public function make_view($file, $content) {
        $view_path = $this->site_path()."/dist/".$file;
        file_put_contents($view_path, $content);
    }

    /**
     * Writes the site script file
     *
     * @return void
     */
    public function make_script() {
        $script_path = $this->site_path()."/dist/site.js";
        file_put_contents($script_path, implode("", $this->scripts()));
        file_put_contents($script_path, file_get_contents(REL_ROOT."/apis/site-builder/auto-run-page.js"), FILE_APPEND);
    }

    /**
     * Gets global scripts to include in the site script file
     *
     * @return void
     */
    public function get_global_scripts() {
        $config = json_decode(file_get_contents("config.json"), true);
        $global_folders = $config['globalFolder'];
        foreach ($global_folders as $folder) {
            foreach ($config['globalScripts'] as $script) {
                $script_path = $folder."/".$script;
                if (file_exists($script_path)) {
                    $this->add_script(file_get_contents($script_path));
                }
            }
        }
    }

    /**
     * Writes the dist config
     *
     * @return void
     */
    public function write_config() {
        $config_path = $this->site_path()."/dist/config.dist.json";
        file_put_contents($config_path, json_encode($this->dist_config(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
}

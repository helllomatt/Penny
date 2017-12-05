<?php

namespace Penny;

class ViewResponse {
    private $route;
    private $site_folder;
    private $config;
    private $theme;
    private $error_theme;
    private $view;

    public function __construct($route, $config, $error = false) {
        $this->route = $route;
        $this->site_folder = $config['folder'];
        $this->config = $config;
        if (!$error) {
            $this->viewExists();
            $this->getDefaultTheme();
        }
        return $this;
    }

    /**
     * Checks to see if the view file exists
     *
     * @return bool
     */
    public function viewExists() {
        if (!isset($this->route->data()['view'])) {
            throw new ResponseException('The view file hasn\'t been defined.');
        }

        $file = REL_ROOT.Config::siteFolder($this->site_folder).'/'.$this->route->data()['view'];
        if (!file_exists($file)) {
            $this->getErrorTheme();
            $this->error(404);
            // throw new ResponseException('The view file specified doesn\'t exist.');
        } else {
            $this->view = $file;
            return true;
        }
    }

    /**
     * Checks to see if the theme exists, and gets the index file
     *
     * @return void
     */
    public function getDefaultTheme() {
        if (!isset($this->config['theme']) || !isset($this->config['theme']['folder'])) {
            throw new ResponseException('Theme not defined in the config.');
        }

        if (!isset($this->route->data()['theme'])) $theme_file = 'index.php';
        else $theme_file = $this->route->data()['theme'];

        $file = REL_ROOT.Config::themeFolder().$this->config['theme']['folder'].'/'.$theme_file;
        if (!file_exists($file)) {
            throw new ResponseException('Theme doesn\'t exist.');
        }

        $this->theme = $file;
    }

    /**
     * Checks to see if the theme exists, and gets the index file
     *
     * @return void
     */
    public function getErrorTheme() {
        if (!isset($this->config['theme'])) {
            throw new ResponseException('Theme not defined in the config.');
        }

        $file = REL_ROOT.Config::themeFolder().$this->config['theme']['folder'].'/error.php';
        if (!file_exists($file)) {
            throw new ResponseException('Theme error page doesn\'t exist.');
        }

        $this->error_theme = $file;
    }

    /**
     * Puts everything together
     *
     * @return void
     */
    public function respond() {
        $view = $this;
        include $this->theme;
    }

    /**
     * Puts the error page together
     *
     * @return void
     */
    public function error($code) {
        http_response_code($code);
        $this->getErrorTheme();
        $view = $this;
        include $this->error_theme;
    }

    /**
     * Includes the view file to output
     *
     * @return void
     */
    public function contents() {
        $view = $this;
        $route = $this->route;
        include $this->view;
    }

    public function includeSiteFile($file, $passing_data = []) {
        $view = $this;
        $route = $this->route;
        $path = REL_ROOT.Config::siteFolder($this->config['folder'])."/".$file;
        if (file_exists($path)) include $path;
    }

    /**
     * Gets a request variable
     *
     * @param  string $key
     * @return any
     */
    public function variable($key) {
        if (!$this->route) return null;
        if (isset($this->route->requestVars()[$key])) return $this->route->requestVars()[$key];
        return null;
    }

    /**
     * Easy way to check and include a theme file
     *
     * @param  string $file
     * @return void
     */
    public function includeThemeFile($file) {
        $file = REL_ROOT.Config::themeFolder().$this->config['theme']['folder'].'/'.$file;
        if (!file_exists($file)) {
            throw new ResponseException('Theme file doesn\'t exist.');
        }

        $view = $this;
        $route = $this->route;
        include $file;
    }

    /**
     * Defines the base href for proper linking
     *
     * @return string html tag of baseref
     */
    public function baseHref() {
        return "<base href='/".trim(str_replace($_SERVER['HTTP_HOST'], "", $this->config['domain']), "/")."/'>";
    }

    /**
     * Returns an HTML string with all of the global scripts
     *
     * @return string
     */
    public static function getGlobalScripts() {
        $scripts = Config::get("globalScripts", true);
        if (!$scripts) return "";

        $html = [];
        foreach ($scripts as $script) {
            $html[] = "<script src='".$script."'></script>";
        }

        return implode("", $html);
    }

    /**
     * Returns an HTML string with all the global styles
     *
     * @return string
     */
    public static function getGlobalStyles() {
        $styles = Config::get("globalStyles", true);
        if (!$styles) return "";

        $html = [];
        foreach ($styles as $style) {
            $html[] = "<link rel='stylesheet' type='text/css' href='".$style."'>";
        }

        return implode("", $html);
    }
}

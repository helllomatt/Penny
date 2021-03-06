<?php

namespace Penny;

class ViewResponse {
    private $route;
    private $site_folder;
    private $config;
    private $theme;
    private $error_theme;
    private $view;
    private $error_code;

    private static $_global_scripts = [];

    public function __construct($route, $config, $error = false) {
        $this->route = $route;
        $this->site_folder = $config['folder'];
        $this->config = $config;

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

        $this->theme = clean_slashes($file);
    }

    /**
     * Returns the full path to the theme file
     *
     * @return string Path to theme file
     */
    public function themeFile() {
        return $this->theme;
    }

    /**
     * Checks to see if the theme exists, and gets the index file
     *
     * @return void
     */
    public function getErrorTheme($error_file = "error.php") {
        if (!isset($this->config['theme'])) {
            throw new ResponseException('Theme not defined in the config.');
        }

        $file = REL_ROOT.Config::themeFolder().$this->config['theme']['folder'].'/'.$error_file;
        if (!file_exists($file)) {
            throw new ResponseException('Theme error page doesn\'t exist.');
        }

        $this->error_theme = clean_slashes($file);
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
        $this->error_code = $code;
        $this->getErrorTheme();
        $view = $this;
        include $this->error_theme;
    }

    /**
     * Returns the complete path to the error theme file
     *
     * @return string Path to the error theme file
     */
    public function errorFile() {
        return $this->error_theme;
    }

    /**
     * Returns the current error code
     *
     * @return number Error code
     */
    public function errorCode() {
        return $this->error_code;
    }

    /**
     * Includes the view file to output
     *
     * @return void
     */
    public function contents() {
        $view = $this;
        $route = $this->route;
        if (file_exists($this->view)) include $this->view;
    }

    /**
     * Includes a file specific to the site root
     *
     * @param string $file File to include from the site folder
     * @param array $passing_data Data to pass to the site file
     */
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
        $server_host = filter_input(INPUT_SERVER, "HTTP_HOST");
        $host = $server_host == null ? "localhost" : $server_host;
        return "<base href='".clean_slashes("/".trim(str_replace($host, "", $this->config['domain']), "/")."/")."'>";
    }

    /**
     * Compiles all scripts to be loaded into HTML
     *
     * @return string
     */
    public function getScripts() {
        $html = [];

        if (isset($this->route->data()['js'])) {
            foreach ($this->route->data()['js'] as $script) {
                if (strpos($script, "/") === 0) {
                    static::addGlobalScript(ltrim($script, "/"));
                    continue;
                } elseif (strpos($script, "://") !== false) {
                    $html[] = "<script type='text/javascript' src='".$script."'></script>";
                    continue;
                } else {
                    $web_path = $script;
                    $path = REL_ROOT.Config::siteFolder($this->config['folder'])."/".$script;
                    $glob = glob($path);

                    if (empty($glob)) $glob = [$path];

                    foreach ($glob as $gpath) {
                        $script = str_replace(REL_ROOT.Config::siteFolder($this->config['folder'])."/", "", $gpath);
                        if (!file_exists($gpath)) continue;
                        if (is_dir($gpath)) {
                            $files = FileSystem::scan($gpath);
                            foreach ($files as $file) {
                                if (substr($file, -2, 2) === "js") {
                                    $html[] = "<script type='text/javascript' src='".clean_slashes($web_path."/".$file)."'></script>";
                                }
                            }
                        } else {
                            if (substr($script, -2, 2) === "js") {
                                $html[] = "<script type='text/javascript' src='".$script."'></script>";
                            }
                        }
                    }
                }
            }
        }

        return static::getGlobalScripts().implode("", $html);
    }

    /**
     * Compiles all scripts to be loaded into HTML
     *
     * @return string [description]
     */
    public function getStyles() {
        $html = [];
        if (isset($this->route->data()['css'])) {
            foreach ($this->route->data()['css'] as $style) {
                if (strpos($style, "://") !== false) {
                    $html[] = "<link rel='stylesheet' type='text/css' href='".$style."'>";
                } else {
                    $web_path = $style;
                    $path = REL_ROOT.Config::siteFolder($this->config['folder'])."/".$style;
                    $glob = glob($path);

                    if (empty($glob)) $glob = [$path];

                    foreach ($glob as $gpath) {
                        $style = str_replace(REL_ROOT.Config::siteFolder($this->config['folder'])."/", "", $gpath);
                        if (!file_exists($gpath)) continue;
                        if (is_dir($path)) {
                            $files = FileSystem::scan($path);
                            foreach ($files as $file) {
                                if (substr($file, -3, 3) === "css") {
                                    $html[] = "<link rel='stylesheet' type='text/css' href='".clean_slashes($web_path."/".$file)."'>";
                                }
                            }
                        } else {
                            if (substr($style, -3, 3) === "css") {
                                $html[] = "<link rel='stylesheet' type='text/css' href='".clean_slashes($style)."'>";
                            }
                        }
                    }
                }
            }
        }

        return static::getGlobalStyles().implode("", $html);
    }

    /**
     * Returns an array of all collected global scripts
     *
     * @return array Collected global scripts
     */
    public static function globalScripts() {
        return static::$_global_scripts;
    }

    /**
     * Overwrites the global scripts array with a new array
     *
     * @param array $scripts Array of scripts to set as global
     */
    public static function setGlobalScripts($scripts) {
        static::$_global_scripts = $scripts;
    }

    /**
     * Adds a script to the global scripts array
     *
     * @param string $script Path to script
     */
    public static function addGlobalScript($script) {
        static::$_global_scripts[] = $script;
    }

    /**
     * Returns an HTML string with all of the global scripts
     *
     * @return string
     */
    public static function getGlobalScripts() {
        $scripts = static::globalScripts();
        if (!$scripts || empty($scripts)) return "";

        $html = [];
        foreach ($scripts as $script) {
            $html[] = "<script type='text/javascript' src='".$script."'></script>";
        }

        return implode("", $html);
    }

    /**
     * Clears all scripts in the global scripts array
     */
    public static function clearGlobalScripts() {
        static::$_global_scripts = [];
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

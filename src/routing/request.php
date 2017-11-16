<?php

namespace Penny;

class Request {
    private $using_method;
    private $found_variables;
    private $for_site;
    private $request_type;
    private $found_real_file = false;
    private $allow_real_file = true;
    private $file_path;

    /**
     * Invokes a request information class. Finds out everything there is to need
     * to know when a request is being handled.
     *
     * @var $argv array
     * @return Penny\Request
     */
    public function __construct($argv = [], $site = '') {
        $this->findMethod();
        $this->findVariables($argv);
        if ($this->using_method !== 'cli') {
            $this->findSite($site);
            $this->checkRealFile();
        }
        return $this;
    }

    /**
     * Finds the method that is being used to make the request
     *
     * @return Penny\Request
     */
    private function findMethod() {
        if (!isset($_SERVER['REQUEST_METHOD'])) {
            if (php_sapi_name() === 'cli') $this->using_method = 'cli';
        } else $this->using_method = strtolower($_SERVER['REQUEST_METHOD']);
        return $this;
    }

    /**
     * Allows the method to be overridden
     *
     * @param  string $to
     * @return Penny\Request
     */
    public function overrideMethod($to) {
        $this->using_method = $to;
        return $this;
    }

    /**
     * Returns the method found to be used when handling the request
     *
     * @return string
     */
    public function method() {
        return $this->using_method;
    }

    /**
     * Defines the type of request call being made
     *
     * @param string $type
     */
    public function setType($type) {
        $this->request_type = $type;
    }

    /**
     * Returns the type of request call being made
     *
     * @return string
     */
    public function type() {
        return $this->request_type;
    }

    /**
     * Finds the variables for a CLI call, or HTTP request
     *
     * @param  array $argv
     * @return Penny\Request
     */
    private function findVariables($argv) {
        if ($this->method() === 'cli') $this->found_variables = $argv;
        else $this->getHttpVariables();
        return $this;
    }

    /**
     * Finds variables in the HTTP headers, POST or GET
     *
     * @return Penny\Request
     */
    private function getHttpVariables() {
        $this->found_variables = array_merge(
            filter_input_array(INPUT_GET) ?: [],
            filter_input_array(INPUT_POST) ?: []);
        // echo "<pre>".print_r($this->found_variables, true)."</pre>";
        return $this;
    }

    /**
     * Returns the variables found
     *
     * @return array
     */
    public function variables() {
        return $this->found_variables;
    }

    /**
     * Adds variables to the request data
     *
     * @param array $vars
     */
    public function addVariables($vars) {
        $this->found_variables = array_merge($this->found_variables, $vars);
    }

    /**
     * Defines the site this request is meant for.
     *
     * @return Penny\Request
     */
    public function findSite($site = '') {
        $found_site = false;
        $domain = clean_slashes($_SERVER['HTTP_HOST']."/".$_SERVER['REDIRECT_URL']."/");

        foreach (Config::getAll() as $site_name => $data) {
            if (!isset($data['domain'])) continue;
            if (strpos($domain, clean_slashes($data['domain'])) === 0) {
                $this->for_site = $site_name;
                $this->found_variables['pennyRoute'] = ltrim(str_replace($data['domain'], "", $domain), "/");
                return $this;
            }
        }

        if (!$found_site) {
            throw new RequestException("Failed to find what site you're looking for.");
        }
    }

    /**
     * Retuens the site the request is for.
     *
     * @return string
     */
    public function site() {
        return $this->for_site;
    }

    /**
     * Checks to see if the route is for a real file
     *
     * @return null
     */
    public function checkRealFile() {
        if (!isset($this->found_variables['pennyRoute'])) {
            throw new RequestException('No route was given in the request.');
        }

        $route = explode('/', $this->found_variables['pennyRoute']);
        $from = $route[0];
        if (in_array($from, ['theme'])) array_shift($route);
        $this->found_variables['pennyRoute'] = rtrim(implode('/', $route), "/");

        if ($from == 'theme') {
            $theme_folder = Config::forSite($this->for_site)['theme']['folder'];
            $path = REL_ROOT.'themes/'.$theme_folder.'/'.$this->found_variables['pennyRoute'];
        } else {
            $config = Config::forSite($this->for_site);
            $site_folder = $config['folder'];
            if (isset($config['asset-folder'])) $asset_folder = $config['asset-folder'];
            else $asset_folder = '';
            $path = REL_ROOT.'sites/'.$site_folder.'/'.$asset_folder.$this->found_variables['pennyRoute'];
        }

        if (file_exists($path) && !is_dir($path)) {
            $extension = (new \SplFileInfo($path))->getExtension();
            $this->found_real_file = true;
            $this->file_path = $path;

            if (in_array($extension, ['php', 'json'])) {
                $this->allow_real_file = false;
            }
        }
    }

    /**
     * Returns the value for if the route is for a real file or not
     *
     * @return boolean
     */
    public function forFile() {
        return $this->found_real_file;
    }

    /**
     * Returns the value for if a real file should be served or not
     *
     * @return boolean
     */
    public function allowedToServe() {
        return $this->allow_real_file;
    }

    /**
     * Returns the path to the file
     *
     * @return string
     */
    public function file() {
        return $this->file_path;
    }

    public function fileType() {
        $extension = pathinfo($this->file_path, PATHINFO_EXTENSION);
        switch ($extension) {
            case "css": return "text/css";
            default: mime_content_type($this->file_path);
        }
    }
}

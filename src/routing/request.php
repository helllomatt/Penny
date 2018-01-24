<?php

namespace Penny;

class Request {
    private $using_method;
    public $found_variables = [];
    private $for_site;
    private $request_type;
    private $found_real_file = false;
    private $allow_real_file = true;
    private $file_path;
    private $domain;

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

        $this->for_site = $site;

        return $this;
    }

    /**
     * Performs the things that need to be done when there's a non-cli request
     *
     * This function is a wrapper to avoid repetition
     */
    public function nonCliRequest() {
        $this->redirectSlash();
        $this->findSite($this->site());
        $this->checkRealFile();
    }

    /**
     * Redirects the page to remove the trailing slash from the URL
     *
     * @param boolean $return - Return only, or actually redirect
     * @return boolean
     */
    private function redirectSlash($return = false) {
        if ($this->found_variables['pennyRoute'] == "") return;
        $server_host = filter_input(INPUT_SERVER, "HTTP_HOST");
        $server_uri = filter_input(INPUT_SERVER, "REQUEST_URI");
        $host = $server_host == null ? "localhost" : $server_host;
        $uri = $server_uri == null ? $this->found_variables['pennyRoute'] : $server_uri;
        $request_path = explode("?", $host.$uri)[0];

        if (substr($request_path, -1, 1) == "/" && $request_path != $host."/") {
            if (!$return) header("Location: //".rtrim($request_path, "/"));
            else return true;
        }

        if ($return) return false;
    }

    /**
     * Finds the method that is being used to make the request
     *
     * @param string $overridden - Method to override the auto detection with
     * @return Penny\Request
     */
    private function findMethod($overridden = null) {
        $server_method = filter_input(INPUT_SERVER, "REQUEST_METHOD");

        if ($server_method == null && $overridden == null) {
            if (php_sapi_name() === 'cli') $this->using_method = 'cli';
        } else $this->using_method = strtolower($server_method == null && $overridden != null ? $overridden : $server_method);

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
    private function findVariables($argv = []) {
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
     * Returns a specific found variable
     *
     * @param  string|int  $key
     * @return any
     */
    public function variable($key) {
        return $this->found_variables[$key];
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
     * Creates/Updates a variable for the request
     *
     * @param string|int $key
     * @param any $val
     */
    public function setVariable($key, $val) {
        $this->found_variables[$key] = $val;
    }

    /**
     * Sets the domain of the current request
     *
     * @param string $domain
     */
    public function setDomain($domain = "") {
        if ($domain == "") {
            throw new RequestException("Domain cannot be blank.");
        }

        $this->domain = clean_slashes($domain);
        return $this;
    }

    /**
     * Returns the domain of the current request, assumes one if it hasn't been set
     *
     * @param string $override_host - String to override host
     * @param string $override_uri - String to override uri
     * @return string domain URL
     */
    public function getDomain($override_host = "", $override_uri = "") {
        if (!$this->domain) {
            $server_host = filter_input(INPUT_SERVER, "HTTP_HOST");
            $server_uri = filter_input(INPUT_SERVER, "REQUEST_URI");

            if ($server_host == null && $override_host != "") $host = $override_host;
            else if ($server_host != null) $host = $server_host;
            else $host = null;

            if ($server_uri == null && $override_uri != "") $uri = $override_uri;
            else if ($server_uri != null) $uri = $server_uri;
            else $uri = null;

            if ($host != null && $uri != null) {
                $uri = explode("?", $uri)[0];
                $this->domain = clean_slashes($host."/".$uri."/");
            } else {
                throw new RequestException("Cannot assume the domain name, and none was given.");
            }
        }

        return $this->domain;
    }

    /**
     * Returns all found sites from the config in order of longest -> shortest
     *
     * @param array $config - Override config
     * @return array
     */
    private function getAllSitesFromConfig($config = []) {
        $everything = empty($config) ? Config::getAll() : $config;
        $sites = [];
        foreach ($everything as $key => $value) {
            if (!isset($value['domain'])) continue;
            $sites[$key] = $value;
        }

        uasort($sites, function($a, $b) {
            return strlen($b['domain']) - strlen($a['domain']);
        });

        return $sites;
    }

    /**
     * Defines the site this request is meant for.
     *
     * Domain: localhost/test
     * Defined Domain: localhost/test
     * Path: test/realfile.txt
     *
     * @param boolean $ignore_check - Ignore real file check
     * @return Penny\Request
     */
    public function findSite($ignore_check = false) {
        $found_site = false;
        $domain = $this->getDomain();

        if (strpos($this->found_variables['pennyRoute'], Config::get("apiIdentity", true)) === false) {
            foreach ($this->getAllSitesFromConfig() as $site_name => $data) {
                if (!isset($data['domain'])) continue;
                if (strpos($domain, clean_slashes($data['domain'])) === 0) {
                    $this->for_site = $site_name;
                    $cleaned_path = ltrim(str_replace($data['domain'], "", $domain), "/");
                    if ($cleaned_path == "") {
                        if ($this->checkRealFile(true)) {
                            $this->found_variables['pennyRoute'] = str_replace($data['domain'], "", $this->found_variables['pennyRoute'])."/";
                        } else {
                            $this->found_variables['pennyRoute'] = "/";
                        }
                    }
                    return $this;
                }
            }

            if (!$found_site && !$ignore_check) {
                if (!$this->checkRealFile(true)) {
                    throw new RequestException("Failed to find what site you're looking for.");
                } else $this->checkRealFile();
            }
        }
    }

    /**
     * Returns the site the request is for.
     *
     * @return string
     */
    public function site() {
        return $this->for_site;
    }

    /**
     * Changes the site without any automation
     *
     * @param string $to - String to change the site to
     */
    public function setSite($to) {
        $this->for_site = $to;
    }

    /**
     * Checks to see if the route is for a real file
     *
     * @return boolean
     */
    public function checkRealFile($return_only = false) {
        if (!isset($this->found_variables['pennyRoute'])) {
            throw new RequestException('No route was given in the request.');
        }

        $route = array_values(array_filter(explode('/', $this->found_variables['pennyRoute'])));
        $from = empty($route) ? "" : $route[0];
        if (in_array($from, ['theme', 'global'])) array_shift($route);
        $this->found_variables['pennyRoute'] = rtrim(implode('/', $route), "/");
        $path = null;

        $global_paths = [];
        $global_folder = Config::get("globalFolder", true);
        if (is_array($global_folder)) {
            foreach ($global_folder as $folder) {
                $global_paths[] = REL_ROOT.$folder."/".$this->found_variables['pennyRoute'];
            }
        } elseif ($global_folder != null) {
            $global_paths[] = REL_ROOT.$global_folder."/".$this->found_variables['pennyRoute'];
        }

        if ($from == 'theme') {
            $theme_folder = Config::forSite($this->for_site)['theme']['folder'];
            $path = REL_ROOT.Config::themeFolder().$theme_folder.'/'.$this->found_variables['pennyRoute'];
        } elseif ($from == 'global' || (($path = $this->file_in_array_exists($global_paths)) !== false)) {
            $global_folder = Config::get("globalFolder");
        } elseif ($this->for_site != null) {
            $config = Config::forSite($this->for_site);
            if (isset($config['asset-folder'])) $asset_folder = $config['asset-folder'];
            else $asset_folder = '';
            $path = REL_ROOT.Config::siteFolder($config['folder']).'/'.$this->found_variables['pennyRoute'];
        }

        if (file_exists($path) && !is_dir($path)) {
            $extension = (new \SplFileInfo($path))->getExtension();

            if (in_array($extension, ['php', 'json'])) {
                if ($return_only) return false;
                $this->allow_real_file = false;
            } else {
                if ($return_only) return true;
                $this->found_real_file = true;
                $this->file_path = $path;
            }
        } elseif ($return_only) return false;

        return true;
    }

    public function file_in_array_exists($array) {
        foreach ($array as $file) {
            if ($this->fileType($file) !== false && file_exists($file)) return $file;
        }
        return false;
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

    /**
     *
     * @param type $given
     * @return string -
     */
    public function fileType($given = null) {
        $path = !$given ? $this->file_path : $given;
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        switch ($extension) {
            case "css": return "text/css";
            case "js": return "text/javascript";
            default: return @mime_content_type($path);
        }
    }
}

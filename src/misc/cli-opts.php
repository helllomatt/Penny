<?php

namespace Penny;

class CliOpts {
    private $requests;
    private $route;
    private $found_options = [];

    public function __construct($request, $route) {
        $this->request = $request;
        $this->route = $route;

        $this->findCommandOpts();
    }

    /**
     * Finds the options defined by the cli command in the args passed via cli
     *
     * @return void
     */
    private function findCommandOpts() {
        $req_vars = $this->request->variables();
        array_splice($req_vars, 0, 2);

        if (!isset($this->route->data()['cli-options'])) return;

        $route_vars = $this->route->data()['cli-options'];
        foreach ($route_vars as $key => $required) {
            if (!in_array($required, ['required', 'optional'])) {
                throw new CliOptException('The cli option "'.$key.'" must be required or optional.');
            }

            $pos = array_search('--'.$key, $req_vars);

            if ($pos === false && $required == 'required') {
                throw new CliOptException('Missing required value: '.$key);
            } elseif ($pos === false) continue;
            else {
                $var = $req_vars[$pos + 1];
                if (substr($var, 0, 1) == '"') {
                    $string = [];
                    for ($i = $pos + 1; $i < count($req_vars); $i++) {
                        $string[] = $req_vars[$i];
                        if (substr($req_vars[$i], -1, 1) == '"') break;
                    }

                    $var = trim(implode(' ', $string), '"');
                }
                $this->found_options[$key] = $var;
            }
        }
    }

    /**
     * Returns any options found
     *
     * @return array
     */
    public function options() {
        return $this->found_options;
    }

    /**
     * Reads input for the CLI
     *
     * @param  string $prompt
     * @return string
     */
    public static function readline($prompt = null) {
        if($prompt) echo $prompt;
        $fp = fopen("php://stdin","r");
        $line = rtrim(fgets($fp, 1024));
        return $line;
    }
}

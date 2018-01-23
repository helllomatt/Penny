<?php

namespace Penny;

class CliOpts {
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
                $string = [];
                for ($i = $pos + 1; $i < count($req_vars); $i++) {
                    if (substr($req_vars[$i], 0, 2) == '--') break;
                    $string[] = $req_vars[$i];
                }

                $var = trim(implode(' ', $string), '"');
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
     * @param string $prompt - Prompt to output before capturing input
     * @param string $file - File to read line from
     * @return string - Captured input
     */
    public static function readline($prompt = null, $file = "php://stdin") {
        if($prompt) echo $prompt;
        $fp = fopen($file, "r");
        $line = rtrim(fgets($fp, 1024));
        return $line;
    }

    /**
     * Outputs text to the console, appending an ENDL automatically.
     *
     * @param string $text - Text to output to the console
     */
    public static function out($text = "") {
        echo "\t".$text.PHP_EOL;
    }
}

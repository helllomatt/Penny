<?php

$name = isset($route->variables()['name']) ? $route->variables()['name'] : null;
echo Test\Greeting::sayHelloToName($name);

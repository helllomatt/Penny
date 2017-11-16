<?php

use Penny\CliOpts;
use Penny\Request;
use Penny\Route;
use PHPUnit\Framework\TestCase;

class CliOptsTest extends TestCase {
    public function testDefault() {
        $args = ['index.php', 'test'];
        $opts = [];
        $request = new Request($args);
        array_splice($args, 0, 1);
        $route = new Route($request, '', ['command' => 'test', 'cli-options' => $opts], $args);

        $cli_opts = new CliOpts($request, $route);
        $this->assertEquals([], $cli_opts->options());
    }

    public function testRequiredOption() {
        $args = ['index.php', 'test', '--name', 'Matt'];
        $opts = ['name' => 'required'];
        $request = new Request($args);
        array_splice($args, 0, 1);
        $route = new Route($request, '', ['command' => 'test', 'cli-options' => $opts], $args);

        $cli_opts = new CliOpts($request, $route);
        $this->assertEquals(['name' => 'Matt'], $cli_opts->options());
    }

    public function testMissingRequiredOption() {
        $args = ['index.php', 'test'];
        $opts = ['name' => 'required'];
        $request = new Request($args);
        array_splice($args, 0, 1);
        $route = new Route($request, '', ['command' => 'test', 'cli-options' => $opts], $args);

        $this->expectException('Penny\CliOptException');
        $cli_opts = new CliOpts($request, $route);
    }

    public function testMissingOptionalOption() {
        $args = ['index.php', 'test'];
        $opts = ['name' => 'optional'];
        $request = new Request($args);
        array_splice($args, 0, 1);
        $route = new Route($request, '', ['command' => 'test', 'cli-options' => $opts], $args);

        $cli_opts = new CliOpts($request, $route);
        $this->assertEquals([], $cli_opts->options());
    }

    public function testOptionalOption() {
        $args = ['index.php', 'test', '--name', 'Matt'];
        $opts = ['name' => 'optional'];
        $request = new Request($args);
        array_splice($args, 0, 1);
        $route = new Route($request, '', ['command' => 'test', 'cli-options' => $opts], $args);

        $cli_opts = new CliOpts($request, $route);
        $this->assertEquals(['name' => 'Matt'], $cli_opts->options());
    }

    public function testGettingStringOption() {
        $args = ['index.php', 'test', '--name', '"Matt', 'is', 'my', 'name"', '--age', '25'];
        $opts = ['name' => 'required', 'age' => 'optional'];
        $request = new Request($args);
        array_splice($args, 0, 1);
        $route = new Route($request, '', ['command' => 'test', 'cli-options' => $opts], $args);

        $cli_opts = new CliOpts($request, $route);
        $this->assertEquals(['name' => 'Matt is my name', 'age' => '25'], $cli_opts->options());
    }
}

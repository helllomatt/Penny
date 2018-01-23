<?php

use Penny\CliOpts;
use PHPUnit\Framework\TestCase;

class CliOptsTest extends TestCase {
    public function testDefault() {
        $args = ['index.php', 'test'];
        $opts = [];

        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(['variables'])->getMock();
        $request->expects($this->any())->method("variables")->willReturn($args);

        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(['data'])->getMock();
        $route->expects($this->any())->method("data")->willReturn(['cli-options' => $opts]);

        $cli_opts = new CliOpts($request, $route);
        $this->assertEquals([], $cli_opts->options());
    }

    public function testRequiredOption() {
        $args = ['index.php', 'test', '--name', 'Matt'];
        $opts = ['name' => 'required'];

        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(['variables'])->getMock();
        $request->expects($this->any())->method("variables")->willReturn($args);

        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(['data'])->getMock();
        $route->expects($this->any())->method("data")->willReturn(['cli-options' => $opts]);

        $cli_opts = new CliOpts($request, $route);
        $this->assertEquals(['name' => 'Matt'], $cli_opts->options());
    }

    public function testBadOption() {
        $args = ["index.php", "test"];
        $opts = ["name" => "bad"];

        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(['variables'])->getMock();
        $request->expects($this->any())->method("variables")->willReturn($args);

        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(['data'])->getMock();
        $route->expects($this->any())->method("data")->willReturn(['cli-options' => $opts]);

        $this->expectException('Penny\CliOptException');
        $cli_opts = new CliOpts($request, $route);
    }

    public function testMissingRequiredOption() {
        $args = ['index.php', 'test'];
        $opts = ['name' => 'required'];

        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(['variables'])->getMock();
        $request->expects($this->any())->method("variables")->willReturn($args);

        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(['data'])->getMock();
        $route->expects($this->any())->method("data")->willReturn(['cli-options' => $opts]);

        $this->expectException('Penny\CliOptException');
        $cli_opts = new CliOpts($request, $route);
    }

    public function testMissingOptionalOption() {
        $args = ['index.php', 'test'];
        $opts = ['name' => 'optional'];

        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(['variables'])->getMock();
        $request->expects($this->any())->method("variables")->willReturn($args);

        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(['data'])->getMock();
        $route->expects($this->any())->method("data")->willReturn(['cli-options' => $opts]);

        $cli_opts = new CliOpts($request, $route);
        $this->assertEquals([], $cli_opts->options());
    }

    public function testOptionalOption() {
        $args = ['index.php', 'test', '--name', 'Matt'];
        $opts = ['name' => 'optional'];

        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(['variables'])->getMock();
        $request->expects($this->any())->method("variables")->willReturn($args);

        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(['data'])->getMock();
        $route->expects($this->any())->method("data")->willReturn(['cli-options' => $opts]);

        $cli_opts = new CliOpts($request, $route);
        $this->assertEquals(['name' => 'Matt'], $cli_opts->options());
    }

    public function testGettingStringOption() {
        $args = ['index.php', 'test', '--name', '"Matt', 'is', 'my', 'name"', '--age', '25'];
        $opts = ['name' => 'required', 'age' => 'optional'];

        $request = $this->getMockBuilder("Penny\Request")->disableOriginalConstructor()->setMethods(['variables'])->getMock();
        $request->expects($this->any())->method("variables")->willReturn($args);

        $route = $this->getMockBuilder("Penny\Route")->disableOriginalConstructor()->setMethods(['data'])->getMock();
        $route->expects($this->any())->method("data")->willReturn(['cli-options' => $opts]);

        $cli_opts = new CliOpts($request, $route);
        $this->assertEquals(['name' => 'Matt is my name', 'age' => '25'], $cli_opts->options());
    }

    public function testOutput() {
        $this->expectOutputString("\tHello".PHP_EOL);
        CliOpts::out("Hello");
    }

    public function testReadLine() {
        $expected = "file1.txt";
        $actual = CliOpts::readline(null, "tests/fs/file1.txt");
        $this->assertEquals($expected, $actual);
    }
}

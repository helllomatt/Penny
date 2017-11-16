<?php

use Penny\FileSystem;
use PHPUnit\Framework\TestCase;

class FileSystemTests extends TestCase {
    public function testScanning() {
        $diff = array_diff(['file1.txt', 'namespace.php', 'folder/'], FileSystem::scan('./tests/fs'));
        $this->assertEquals([], $diff);
    }

    public function testScanningRecursively() {
        $files = FileSystem::scan('./tests/fs', ['recursive' => true]);
        $good = true;
        foreach ($files as $file) {
            if (is_array($file)) continue;
            if (!in_array($file, ['file1.txt', 'namespace.php'])) $good = false;
        }
        if (!array_key_exists('folder', $files)) $good = false;
        if ($files['folder'] != ['file3.txt']) $good = false;

        $this->assertTrue($good);
    }

    public function testFlatReturn() {
        $files = FileSystem::scan('./tests/fs', ['recursive' => true, 'flat' => true]);
        $diff = array_diff(['file1.txt', 'namespace.php', 'folder/', 'folder/file3.txt'], $files);
        $this->assertEquals([], $diff);
    }

    public function testGettingNamespace() {
        $namespace = FileSystem::findNamespace('./tests/fs/namespace.php');
        $this->assertEquals('FileSystemTest', $namespace);
    }
}

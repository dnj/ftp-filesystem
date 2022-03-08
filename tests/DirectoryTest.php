<?php

namespace tests\directory;

use dnj\Filesystem\Exceptions\IOException;
use dnj\Filesystem\Local\Directory;
use dnj\Filesystem\Local\File;
use PHPUnit\Framework\TestCase;

final class DirectoryTest extends TestCase
{
    public function testConstructor(): void
    {
        $directory = new Directory('/home/test');
        $this->assertSame($directory->getBasename(), 'test');
        $this->assertSame($directory->getDirname(), '/home');
    }

    public function testGetPath(): void
    {
        $directory = new Directory('/home/test');
        $this->assertSame($directory->getPath(), '/home/test');
        $directory = new Directory('/home/test/');
        $this->assertSame($directory->getPath(), '/home/test');
    }

    public function testGetDirectory(): void
    {
        $directory = new Directory('/home/test');
        $parent = $directory->getDirectory();
        $this->assertInstanceOf(Directory::class, $parent);
        $this->assertSame($parent->getPath(), '/home');
    }

    public function testDirectory(): void
    {
        $directory = new Directory('/home/test');
        $child = $directory->directory('test2');
        $this->assertInstanceOf(Directory::class, $child);
        $this->assertSame($child->getPath(), '/home/test/test2');
    }

    public function testFile(): void
    {
        $directory = new Directory('/home/test');
        $child = $directory->file('test2');
        $this->assertInstanceOf(File::class, $child);
        $this->assertSame($child->getPath(), '/home/test/test2');
    }

    public function testGetRelativePath(): void
    {
        $dir = new Directory('/home');
        $directory = new Directory('/home/test');
        $this->assertSame($directory->getRelativePath($dir), 'test');

        $dir = new Directory('/home2');
        $directory = new Directory('/home/test');
        $this->expectException(IOException::class);
        $directory->getRelativePath($dir);
    }

    public function testItems(): void
    {
        $dir = __DIR__.'/'.time().'-'.rand(0, 1000).'-dir';
        mkdir($dir);
        file_put_contents("{$dir}/file1", 'test');
        mkdir("{$dir}/dir1");
        mkdir("{$dir}/dir1/dir2");
        file_put_contents("{$dir}/dir1/file2", 'test');
        try {
            $directory = new Directory($dir);
            $files = $directory->files(false);
            $this->assertIsIterable($files);
            $array = iterator_to_array($files);
            $this->assertCount(1, $array);
            $this->assertContainsOnlyInstancesOf(File::class, $array);
            $this->assertSame("{$dir}/file1", $array[0]->getPath());

            $files = $directory->files(true);
            $this->assertIsIterable($files);
            $array = iterator_to_array($files);
            $this->assertCount(2, $array);
            $this->assertContainsOnlyInstancesOf(File::class, $array);

            $directories = $directory->directories(false);
            $this->assertIsIterable($directories);
            $array = iterator_to_array($directories);
            $this->assertCount(1, $array);
            $this->assertContainsOnlyInstancesOf(Directory::class, $array);
            $this->assertSame("{$dir}/dir1", $array[0]->getPath());

            $directories = $directory->directories(true);
            $this->assertIsIterable($directories);
            $array = iterator_to_array($directories);
            $this->assertCount(2, $array);
            $this->assertContainsOnlyInstancesOf(Directory::class, $array);

            $items = $directory->items(false);
            $this->assertIsIterable($items);
            $array = iterator_to_array($items);
            $this->assertCount(2, $array);

            $items = $directory->items(true);
            $this->assertIsIterable($items);
            $array = iterator_to_array($items);
            $this->assertCount(4, $array);
        } finally {
            if (is_file($dir.'/file1')) {
                unlink($dir.'/file1');
            }
            if (is_file($dir.'/dir1/file2')) {
                unlink($dir.'/dir1/file2');
            }
            if (is_dir($dir.'/dir1/dir2')) {
                rmdir($dir.'/dir1/dir2');
            }
            if (is_dir($dir.'/dir1')) {
                rmdir($dir.'/dir1');
            }
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    public function testMake(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'-dir';
        try {
            $directory = new Directory($path);
            $this->assertDirectoryDoesNotExist($path);
            $directory->make();
            $this->assertDirectoryExists($path);

            $directory = new Directory('/proc/test');
            $this->expectException(IOException::class);
            $directory->make();
        } finally {
            if (is_dir($path)) {
                rmdir($path);
            }
        }
    }

    public function testIsEmpty(): void
    {
        $directory = new Directory('/dev/');
        $this->assertFalse($directory->isEmpty());
    }

    public function testSize(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'-dir';
        mkdir($path);
        $data = 'test';
        file_put_contents($path.'/file1', $data);
        try {
            $directory = new Directory($path);
            $this->assertSame(strlen($data), $directory->size());
        } finally {
            if (is_file($path.'/file1')) {
                unlink($path.'/file1');
            }
            if (is_dir($path)) {
                rmdir($path);
            }
        }
    }

    public function testDelete(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'-dir';
        try {
            mkdir($path);

            $directory = new Directory($path);
            $this->assertDirectoryExists($path);
            $directory->delete();
            $this->assertDirectoryDoesNotExist($path);
            $directory->delete();

            $directory = new Directory('/proc/self/');
            $this->expectException(IOException::class);
            $directory->delete();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testRename(): void
    {
        $directory = __DIR__.'/';
        $path = $directory.time().'-'.rand(0, 1000).'-dir';
        $newName = rand(0, 1000).'-dir';
        $newPath = $directory.$newName;
        try {
            mkdir($path);
            $directory = new Directory($path);
            $this->assertDirectoryExists($path);
            $directory->rename($newName);

            $this->assertDirectoryDoesNotExist($path);
            $this->assertDirectoryExists($newPath);

            $this->assertSame($directory->getBasename(), $newName);
            $this->assertSame($directory->getPath(), $newPath);

            $directory = new Directory($path);
            $this->expectException(IOException::class);
            $directory->rename($newName);
        } finally {
            if (is_dir($path)) {
                rmdir($path);
            }
            if (is_dir($newPath)) {
                rmdir($newPath);
            }
        }
    }

    public function testExists(): void
    {
        $directory = new Directory('/non-existing-directory/non-existing-file');
        $this->assertFalse($directory->exists());
        $directory = new Directory(__FILE__);
        $this->assertFalse($directory->exists());
        $directory = new Directory(__DIR__);
        $this->assertTrue($directory->exists());
    }

    public function testGetRealPath(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            symlink(__DIR__, $path);
            $directory = new Directory($path);
            $this->assertSame($directory->getRealPath(), __DIR__);

            $directory = new Directory('/non-existing-directory');
            $this->expectException(IOException::class);
            $directory->getRealPath();
        } finally {
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }

    public function testSerialize(): void
    {
        $directory = new Directory('/home/dir');
        $serialized = $directory->serialize();
        $directory->directory = '';
        $directory->basename = '';
        $directory->unserialize($serialized);
        $this->assertSame($directory->directory, '/home');
        $this->assertSame($directory->basename, 'dir');
    }
}

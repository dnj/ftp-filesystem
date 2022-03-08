<?php

namespace tests;

use dnj\Filesystem\Exceptions\IOException;
use dnj\Filesystem\Local\Directory;
use dnj\Filesystem\Local\File;
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testConstructor(): void
    {
        $file = new File('/home/test');
        $this->assertSame($file->getBasename(), 'test');
        $this->assertSame($file->getDirname(), '/home');
    }

    public function testGetPath(): void
    {
        $file = new File('/home/test');
        $this->assertSame($file->getPath(), '/home/test');
        $file = new File('/home/test/');
        $this->assertSame($file->getPath(), '/home/test');
    }

    public function testGetDirectory(): void
    {
        $file = new File('/home/test');
        $parent = $file->getDirectory();
        $this->assertInstanceOf(Directory::class, $parent);
        $this->assertSame($parent->getPath(), '/home');
    }

    public function testGetRelativePath(): void
    {
        $dir = new Directory('/home');
        $file = new File('/home/test');
        $this->assertSame($file->getRelativePath($dir), 'test');

        $dir = new Directory('/home2');
        $file = new File('/home/test');
        $this->expectException(IOException::class);
        $file->getRelativePath($dir);
    }

    public function testGetExtension(): void
    {
        $file = new File('/home/test');
        $this->assertSame($file->getExtension(), '');

        $file = new File('/home/test.txt');
        $this->assertSame($file->getExtension(), 'txt');

        $file = new File('/home/.htaccess');
        $this->assertSame($file->getExtension(), 'htaccess');
    }

    public function testIsEmpty(): void
    {
        $file = new File('/dev/null');
        $this->assertTrue($file->isEmpty());
    }

    public function testWrite(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            $file = new File($path);
            $data = 'test - '.rand(0, 1000);
            $file->write($data);
            $this->assertFileExists($path);
            $this->assertSame(file_get_contents($path), $data);

            $file = new File('/some-non-existing-dir/non-existing-file');
            $this->expectException(IOException::class);
            $file->write($data);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testRead(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            $data = 'test - '.rand(0, 1000);
            file_put_contents($path, $data);
            $file = new File($path);
            $this->assertSame($file->read(), $data);
            $this->assertSame($file->read(4), 'test');

            $file = new File('/non-existing-file');
            $this->expectException(IOException::class);
            $file->read();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testSize(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            $data = 'test - '.rand(0, 1000);
            file_put_contents($path, $data);
            $file = new File($path);
            $this->assertSame($file->size(), strlen($data));

            $file = new File('/non-existing-file');
            $this->expectException(IOException::class);
            $file->size();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testMove(): void
    {
        $source = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        $dest = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            $data = time().'-'.rand(0, 1000);
            file_put_contents($source, $data);

            $sourceFile = new File($source);
            $this->assertFileExists($source);

            $destFile = new File($dest);
            $this->assertFileDoesNotExist($dest);

            $sourceFile->move($destFile);
            $this->assertFileExists($dest);
            $this->assertFileDoesNotExist($source);

            $this->assertSame(file_get_contents($dest), $data);
        } finally {
            if (is_file($source)) {
                unlink($source);
            }
            if (is_file($dest)) {
                unlink($dest);
            }
        }
    }

    public function testDelete(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            $data = time().'-'.rand(0, 1000);
            file_put_contents($path, $data);

            $file = new File($path);
            $this->assertFileExists($path);
            $file->delete();
            $this->assertFileDoesNotExist($path);
            $file->delete();

            $file = new File('/proc/self/limits');
            $this->expectException(IOException::class);
            $file->delete();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testRename(): void
    {
        $directory = __DIR__.'/';
        $path = $directory.time().'-'.rand(0, 1000).'.test';
        $newName = rand(0, 1000).'.test';
        $newPath = $directory.$newName;
        try {
            $data = time().'-'.rand(0, 1000);
            file_put_contents($path, $data);

            $file = new File($path);
            $this->assertFileExists($path);
            $file->rename($newName);

            $this->assertFileDoesNotExist($path);
            $this->assertFileExists($newPath);

            $this->assertSame($file->getBasename(), $newName);
            $this->assertSame($file->getPath(), $newPath);
            $this->assertSame(file_get_contents($newPath), $data);

            $file = new File($path);
            $this->expectException(IOException::class);
            $file->rename($newName);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
            if (is_file($newPath)) {
                unlink($newPath);
            }
        }
    }

    /**
     * @depends testWrite
     */
    public function testMd5(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            $file = new File($path);
            $data = 'test - '.rand(0, 1000);
            $file->write($data);
            $this->assertSame($file->md5(), md5($data));
            $this->assertSame($file->md5(true), md5($data, true));

            $file = new File('/non-existing-file');
            $this->expectException(IOException::class);
            $file->md5();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    /**
     * @depends testWrite
     */
    public function testSha1(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            $file = new File($path);
            $data = 'test - '.rand(0, 1000);
            $file->write($data);
            $this->assertSame($file->sha1(), sha1($data));
            $this->assertSame($file->sha1(true), sha1($data, true));

            $file = new File('/non-existing-file');
            $this->expectException(IOException::class);
            $file->sha1();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testCopy(): void
    {
        $source = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        $dest = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            $data = time().'-'.rand(0, 1000);
            file_put_contents($source, $data);

            $sourceFile = new File($source);
            $this->assertFileExists($source);

            $destFile = new File($dest);
            $this->assertFileDoesNotExist($dest);

            $sourceFile->copyTo($destFile);
            $this->assertFileExists($dest);
            $this->assertFileExists($source);

            $this->assertFileEquals($source, $dest);

            $destFile = new File('/non-existing-directory/non-existing-file');
            $this->expectException(IOException::class);
            $sourceFile->copyTo($destFile);
        } finally {
            if (is_file($source)) {
                unlink($source);
            }
            if (is_file($dest)) {
                unlink($dest);
            }
        }
    }

    public function testExists(): void
    {
        $file = new File('/non-existing-directory/non-existing-file');
        $this->assertFalse($file->exists());
        $file = new File(__FILE__);
        $this->assertTrue($file->exists());
    }

    public function testGetRealPath(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            symlink(__FILE__, $path);
            $file = new File($path);
            $this->assertSame($file->getRealPath(), __FILE__);

            $file = new File('/non-existing-directory/non-existing-file');
            $this->expectException(IOException::class);
            $file->getRealPath();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testTouch(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            $file = new File($path);
            $this->assertFileDoesNotExist($path);
            $file->touch();
            $this->assertFileExists($path);

            $file = new File('/non-existing-directory/non-existing-file');
            $this->expectException(IOException::class);
            $file->touch();
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testAppend(): void
    {
        $path = __DIR__.'/'.time().'-'.rand(0, 1000).'.test';
        try {
            $data = 'test - '.rand(0, 1000);
            $newData = ' - '.rand(0, 1000);
            file_put_contents($path, $data);
            $file = new File($path);
            $file->append($newData);
            $this->assertSame(file_get_contents($path), $data.$newData);

            $file = new File('/some-non-existing-dir/non-existing-file');
            $this->expectException(IOException::class);
            $file->append($data);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testSerialize(): void
    {
        $file = new File('/home/file');
        $serialized = $file->serialize();
        $file->directory = '';
        $file->basename = '';
        $file->unserialize($serialized);
        $this->assertSame($file->directory, '/home');
        $this->assertSame($file->basename, 'file');
    }
}

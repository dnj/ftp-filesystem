<?php

namespace dnj\Filesystem\Ftp;

use dnj\Filesystem\Contracts\IDirectory;
use dnj\Filesystem\Contracts\IFile;
use dnj\Filesystem\Exceptions\IOException;
use dnj\Filesystem\File as FileAbstract;
use dnj\Filesystem\Local;
use dnj\Filesystem\Tmp;
use dnj\FTP\Contracts\IException;
use dnj\FTP\Contracts\ModeType;

class File extends FileAbstract implements \JsonSerializable
{
    use NodeTrait;

    public function write(string $data): void {
        try {
            $this->connection->put($this->getPath(), $data, false, ModeType::BINARY);
        } catch (IException $e) {
            throw IOException::fromLastError($e);
        }
    }

    public function read(int $length = 0): string {
        try {
            return $this->connection->get($this->getPath(), ModeType::BINARY, $length ?: null);
        } catch (IException $e) {
            throw IOException::fromLastError($e);
        }
    }

    public function size(): int {
        try {
            return $this->connection->size($this->getPath());
        } catch (IException $e) {
            throw IOException::fromLastError($e);
        }
    }

    public function move(IFile $dest): void {
        if ($dest instanceof self) {
            $this->connection->rename($this->getPath(), $dest->getPath());
        }
        $this->copyTo($dest);
        $this->delete();
    }

    public function delete(): void {
        if (!$this->exists()) {
            return;
        }
        try {
            $this->connection->delete($this->getPath());
        } catch (IException $e) {
            throw IOException::fromLastError($e);
        }
    }

    public function copyTo(IFile $dest): void {
        if (!$dest instanceof Local\File) {
            $tmp = new Tmp\File();
            $this->copyTo($tmp);
            $dest->copyFrom($tmp);

            return;
        }

        try {
            $this->connection->download($this->getPath(), $dest->getPath());
        } catch (IException $e) {
            throw IOException::fromLastError($this);
        }
    }

    public function copyFrom(IFile $source): void {
        if (!$source instanceof Local\File) {
            $tmp = new Tmp\File();
            $source->copyTo($tmp);
            $this->copyFrom($tmp);

            return;
        }
        try {
            $this->connection->upload($source->getPath(), $this->getPath());
        } catch (IException $e) {
            throw IOException::fromLastError($this);
        }
    }

    public function exists(): bool {
        try {
            return $this->connection->isFile($this->getPath());
        } catch (IException $e) {
            throw IOException::fromLastError($e);
        }
    }
}

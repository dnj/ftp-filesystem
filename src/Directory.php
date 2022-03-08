<?php

namespace dnj\Filesystem\Ftp;

use dnj\Filesystem\Contracts\IDirectory;
use dnj\Filesystem\Directory as DirectoryAbstract;
use dnj\Filesystem\Exceptions\IOException;
use dnj\FTP\Contracts\IException;

class Directory extends DirectoryAbstract implements \JsonSerializable
{
    use NodeTrait;
/**
     * @return \Generator<File>
     */
    public function files(bool $recursively): \Generator
    {
        foreach ($this->items($recursively) as $item) {
            if ($item instanceof File) {
                yield $item;
            }
        }
    }

    /**
     * @return \Generator<self>
     */
    public function directories(bool $recursively): \Generator
    {
        foreach ($this->items($recursively) as $item) {
            if ($item instanceof self) {
                yield $item;
            }
        }
    }

    /**
     * @return \Generator<self|File>
     */
    public function items(bool $recursively): \Generator
    {
        if ($recursively) {
            $items = iterator_to_array($this->items(false));
            yield from $items;
            foreach ($items as $item) {
                if ($item instanceof self) {
                    yield from $item->items(true);
                }
            }
        } else {
            try {
                $path = $this->getPath();
                $items = $this->connection->ls($path);
                if ($items) {
                    foreach ($items as $item) {
                        if ($item['name'] == '.' or $item['name'] == '..') {
                            continue;
                        }
                        if ($item['type'] == 'dir') {
                            yield $this->directory($item['name']);
                        } elseif ($item['type'] == 'file') {
                            yield $this->file($item['name']);
                        }
                    }
                }
            } catch (IException $e) {
                throw IOException::fromLastError($this);
            }
        }
    }

    public function make(bool $recursively = true): void
    {
        try {
            $this->connection->mkdir($this->getPath(), $recursively);
        } catch (IException $e) {
            throw IOException::fromLastError($this);
        }
    }

    public function size(bool $recursively = true): int
    {
        $size = 0;
        foreach ($this->files($recursively) as $file) {
            $size += $file->size();
        }

        return $size;
    }

    public function move(IDirectory $dest): void
    {
        if (!$dest instanceof self) {
            throw new IOException($dest, 'unsupported dest');
        }
        try {
            $this->connection->rename($this->getPath(), $dest->getPath());
        } catch (IException $e) {
            throw IOException::fromLastError($this);
        }
        $this->directory = $dest->getPath();
    }

    public function file(string $name): File
    {
        return new File($this->getPath().'/'.$name, $this->connection);
    }

    public function directory(string $name): self
    {
        return new self($this->getPath().'/'.$name, $this->connection);
    }

    public function exists(): bool
    {
        return $this->connection->isDir($this->getPath());
    }

    public function delete(): void
    {
        if (!$this->exists()) {
            return;
        }
        parent::delete();
        try {
            $this->connection->rmdir($this->getPath());
        } catch (IException $e) {
            throw IOException::fromLastError($this);
        }
    }
}

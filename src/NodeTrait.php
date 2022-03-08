<?php

namespace dnj\Filesystem\Ftp;

use dnj\Filesystem\Contracts\IDirectory;
use dnj\Filesystem\Exceptions\IOException;
use dnj\FTP\Contracts\IConnection;
use dnj\FTP\Contracts\IException;

trait NodeTrait
{
    protected IConnection $connection;

    public function __construct(string $path, IConnection $connection)
    {
        parent::__construct($path);
        $this->connection = $connection;
    }

    public function getConnection(): IConnection
    {
        return $this->connection;
    }

    public function rename(string $newName): void
    {
        try {
            $this->connection->rename($this->getPath(), $this->directory.'/'.$newName);
        } catch (IException $e) {
            throw IOException::fromLastError($e);
        }
        $this->basename = $newName;
    }

    public function getRelativePath(IDirectory $base): string
    {
        if (!$base instanceof Directory) {
            throw new \InvalidArgumentException('base is not a '.Directory::class.' directory!');
        }

        return parent::getRelativePath($base);
    }

    public function getDirectory(): Directory
    {
        return new Directory($this->directory, $this->connection);
    }

    /**
     * @return array{connection:IConnection,path:string}
     */
    public function jsonSerialize(): array {
        return [
            'connection' => $this->connection,
            'path' => $this->getPath(),
        ];
    }

    public function serialize(): string
    {
        return serialize($this->jsonSerialize());
    }

    public function unserialize($data)
    {
        /** @var array{connection:IConnection,path:string} $data */
        $data = unserialize($data);
        $this->connection = $data['connection'];
        $this->directory = dirname($data['path']);
        $this->basename = basename($data['path']);
    }
}

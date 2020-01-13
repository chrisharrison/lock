<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockDriver;

use ChrisHarrison\Lock\Lock;
use ChrisHarrison\Lock\LockSerialiser\LockSerialiser;

final class FilesystemLockDriver implements LockDriver
{
    private $lockPathPrefix;
    private $serialiser;

    public function __construct(string $lockPathPrefix, LockSerialiser $serialiser)
    {
        $this->lockPathPrefix = $lockPathPrefix;
        $this->serialiser = $serialiser;
    }

    public function read(string $id): Lock
    {
        $file = file_get_contents($this->determineLockFilePath($id));
        if ($file === false) {
            return Lock::null();
        }
        return $this->serialiser->unserialise($file);
    }

    public function write(Lock $lock): void
    {
        file_put_contents(
            $this->determineLockFilePath($lock->getId()),
            $this->serialiser->serialise($lock)
        );
    }

    public function delete(string $id): void
    {
        unlink($this->determineLockFilePath($id));
    }

    private function determineLockFilePath(string $id): string
    {
        return $this->lockPathPrefix . '/' . md5($id);
    }
}

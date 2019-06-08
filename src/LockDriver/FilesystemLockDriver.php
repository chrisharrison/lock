<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockDriver;

use ChrisHarrison\Lock\Lock;
use ChrisHarrison\Lock\LockSerialiser\LockSerialiser;

final class FilesystemLockDriver implements LockDriver
{
    private $lockPath;
    private $serialiser;

    public function __construct(string $lockPath, LockSerialiser $serialiser)
    {
        $this->lockPath = $lockPath;
        $this->serialiser = $serialiser;
    }

    public function read(): Lock
    {
        $file = file_get_contents($this->lockPath);
        if ($file === false) {
            return Lock::null();
        }
        return $this->serialiser->unserialise($file);
    }

    public function write(Lock $lock): void
    {
        file_put_contents($this->lockPath, $this->serialiser->serialise($lock));
    }
}

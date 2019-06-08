<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockDriver;

use ChrisHarrison\Lock\Lock;

final class InMemoryLockDriver implements LockDriver
{
    private $lock;

    public function __construct(Lock $initialLock)
    {
        $this->lock = $initialLock;
    }

    public function read(): Lock
    {
        return $this->lock;
    }

    public function write(Lock $lock): void
    {
        $this->lock = $lock;
    }
}

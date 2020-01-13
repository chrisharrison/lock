<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockDriver;

use ChrisHarrison\Lock\Lock;

final class InMemoryLockDriver implements LockDriver
{
    private $locks;

    public function read(string $id): Lock
    {
        return ($this->locks[$id]) ?? Lock::null();
    }

    public function write(Lock $lock): void
    {
        $this->locks[$lock->getId()] = $lock;
    }

    public function delete(string $id): void
    {
        unset($this->locks[$id]);
    }
}

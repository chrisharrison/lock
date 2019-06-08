<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockInspector;

use ChrisHarrison\Lock\Lock;

interface LockInspector
{
    public function hasExpired(Lock $lock): bool;
    public function wasLockedBy(Lock $lock, string $actor): bool;
}

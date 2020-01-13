<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockGuard;

use ChrisHarrison\Lock\Lock;
use DateTimeInterface;

interface LockGuard
{
    public function protect(
        string $id,
        string $actor,
        DateTimeInterface $until,
        ?callable $onLockGained = null,
        ?callable $onLockFailed = null,
        ?callable $onAttempt = null
    ): void;
    public function gainLock(Lock $newLock): bool;
    public function releaseLock(Lock $lock): void;
}

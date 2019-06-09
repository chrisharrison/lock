<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockGuard;

use ChrisHarrison\Lock\Lock;
use DateTimeInterface;

interface LockGuard
{
    public function protect(
        string $actor,
        DateTimeInterface $until,
        callable $onLockGained,
        callable $onLockFailed,
        callable $onAttempt
    ): void;
    public function gainLock(Lock $newLock): bool;
    public function releaseLock(): void;
}
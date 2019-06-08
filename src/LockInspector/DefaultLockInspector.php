<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockInspector;

use ChrisHarrison\Clock\Clock;
use ChrisHarrison\Lock\Lock;

final class DefaultLockInspector implements LockInspector
{
    private $clock;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
    }

    public function hasExpired(Lock $lock): bool
    {
        return $this->clock->now() > $lock->getUntil();
    }

    public function wasLockedBy(Lock $lock, string $actor): bool
    {
        return $lock->getActor() === $actor;
    }
}

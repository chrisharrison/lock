<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\Guard;

use ChrisHarrison\Lock\Lock;
use DateTimeInterface;

interface Guard
{
    public function protect(string $actor, DateTimeInterface $until, callable $callback): bool;
    public function gainLock(Lock $newLock): bool;
    public function releaseLock(): void;
}
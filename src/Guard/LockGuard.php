<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\Guard;

use ChrisHarrison\Lock\Lock;
use ChrisHarrison\Lock\LockDriver\LockDriver;
use ChrisHarrison\Lock\LockInspector\LockInspector;
use DateTimeInterface;

final class LockGuard implements Guard
{
    private $maxAttempts;
    private $attemptIntervalSeconds;
    private $lockDriver;
    private $lockInspector;

    public function __construct(
        int $maxAttempts,
        int $attemptIntervalSeconds,
        LockDriver $lockDriver,
        LockInspector $lockInspector
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->attemptIntervalSeconds = $attemptIntervalSeconds;
        $this->lockDriver = $lockDriver;
        $this->lockInspector = $lockInspector;
    }

    public function protect(string $actor, DateTimeInterface $until, callable $callback): bool
    {
        $newLock = Lock::fromNative([
            'actor' => $actor,
            'until' => $until->format(DATE_RFC3339),
        ]);

        $attempts = 0;
        while ($attempts < $this->maxAttempts) {
            if ($this->gainLock($newLock)) {
                $callback();
                $this->releaseLock();
                return true;
            }
            sleep($this->attemptIntervalSeconds);
            $attempts++;
        }
        return false;
    }

    public function gainLock(Lock $newLock): bool
    {
        $oldLock = $this->lockDriver->read();
        if ($oldLock->isNull()) {
            return $this->lock($newLock);
        }
        if ($this->lockInspector->hasExpired($oldLock)) {
            return $this->lock($newLock);
        }
        return ($this->lockInspector->wasLockedBy($oldLock, $newLock->getActor()));
    }

    private function lock(Lock $lock): bool
    {
        $this->lockDriver->write($lock);
        return $this->gainLock($lock);
    }

    public function releaseLock(): void
    {
        $this->lockDriver->write(Lock::null());
    }
}

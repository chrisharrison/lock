<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockGuard;

use ChrisHarrison\Clock\Clock;
use ChrisHarrison\Lock\Lock;
use ChrisHarrison\Lock\LockAttempt;
use ChrisHarrison\Lock\LockDriver\LockDriver;
use ChrisHarrison\Lock\LockFailed;
use ChrisHarrison\Lock\LockGained;
use ChrisHarrison\Lock\LockInspector\LockInspector;
use DateTimeInterface;

final class DefaultLockGuard implements LockGuard
{
    private $maxAttempts;
    private $attemptIntervalSeconds;
    private $lockDriver;
    private $lockInspector;
    private $clock;

    public function __construct(
        int $maxAttempts,
        int $attemptIntervalSeconds,
        LockDriver $lockDriver,
        LockInspector $lockInspector,
        Clock $clock
    ) {
        $this->maxAttempts = $maxAttempts;
        $this->attemptIntervalSeconds = $attemptIntervalSeconds;
        $this->lockDriver = $lockDriver;
        $this->lockInspector = $lockInspector;
        $this->clock = $clock;
    }

    public function protect(
        string $actor,
        DateTimeInterface $until,
        ?callable $onLockGained = null,
        ?callable $onLockFailed = null,
        ?callable $onAttempt = null
    ): void {
        $newLock = Lock::fromNative([
            'actor' => $actor,
            'until' => $until->format(DATE_RFC3339),
        ]);

        $startTime = $this->clock->now();

        $attempt = 0;
        while ($attempt < $this->maxAttempts) {
            $attempt++;
            if ($this->gainLock($newLock)) {
                if ($onLockGained !== null) {
                    $onLockGained(new LockGained(
                        $attempt,
                        $this->maxAttempts,
                        $this->clock->now()->getTimestamp() - $startTime->getTimestamp()
                    ));
                }
                $this->releaseLock();
                return;
            }
            if ($attempt === $this->maxAttempts) {
                continue;
            }
            if ($onAttempt !== null) {
                $onAttempt(new LockAttempt(
                    $attempt,
                    $this->maxAttempts,
                    $this->clock->now()->getTimestamp() - $startTime->getTimestamp(),
                    $this->attemptIntervalSeconds
                ));
            }
            sleep($this->attemptIntervalSeconds);
        }

        if ($onLockFailed !== null) {
            $onLockFailed(new LockFailed(
                $attempt,
                $this->clock->now()->getTimestamp() - $startTime->getTimestamp()
            ));
        }
        return;
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

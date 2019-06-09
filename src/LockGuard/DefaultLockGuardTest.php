<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\Guard;

use ChrisHarrison\Clock\FrozenClock;
use ChrisHarrison\Lock\Lock;
use ChrisHarrison\Lock\LockAttempt;
use ChrisHarrison\Lock\LockDriver\InMemoryLockDriver;
use ChrisHarrison\Lock\LockFailed;
use ChrisHarrison\Lock\LockGained;
use ChrisHarrison\Lock\LockGuard\DefaultLockGuard;
use ChrisHarrison\Lock\LockInspector\DefaultLockInspector;
use const DATE_RFC3339;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DefaultLockGuardTest extends TestCase
{
    private function now(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('U', '100');
    }

    private function laterTime(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('U', '101');
    }

    private function earlierTime(): DateTimeImmutable
    {
        return DateTimeImmutable::createFromFormat('U', '99');
    }

    private function sut(?array $options = [])
    {
        $useOptions = [
            'driver' => $options['driver'] ?? new InMemoryLockDriver(Lock::null()),
            'maxAttempts' => $options['maxAttempts'] ?? 1,
        ];

        $clock = new FrozenClock($this->now());

        return new DefaultLockGuard(
            $useOptions['maxAttempts'],
            0,
            $useOptions['driver'],
            new DefaultLockInspector($clock),
            $clock
        );
    }

    private function nonExpiredLockByAlice()
    {
        return Lock::fromNative([
            'actor' => 'alice',
            'until' => $this->laterTime()->format(DATE_RFC3339),
        ]);
    }

    private function expiredLockByAlice()
    {
        return Lock::fromNative([
            'actor' => 'alice',
            'until' => $this->earlierTime()->format(DATE_RFC3339),
        ]);
    }

    private function nonExpiredLockByBob()
    {
        return Lock::fromNative([
            'actor' => 'bob',
            'until' => $this->laterTime()->format(DATE_RFC3339),
        ]);
    }

    private function expiredLockByBob()
    {
        return Lock::fromNative([
            'actor' => 'bob',
            'until' => $this->earlierTime()->format(DATE_RFC3339),
        ]);
    }

    public function test_it_releases_lock()
    {
        $driver = new InMemoryLockDriver($this->nonExpiredLockByAlice());
        $sut = $this->sut([
            'driver' => $driver,
        ]);

        $this->assertNotEquals(null, $driver->read()->toNative());
        $sut->releaseLock();
        $this->assertEquals(null, $driver->read()->toNative());
    }

    public function test_it_gains_lock_if_no_lock_exists()
    {
        $driver = new InMemoryLockDriver(Lock::null());
        $sut = $this->sut([
            'driver' => $driver,
        ]);
        $lock = $this->nonExpiredLockByAlice();
        $didGainLock = $sut->gainLock($lock);

        $this->assertTrue($didGainLock);
        $this->assertEquals($lock, $driver->read());
    }

    public function test_it_gains_lock_if_current_lock_has_expired()
    {
        $driver = new InMemoryLockDriver($this->expiredLockByAlice());
        $sut = $this->sut([
            'driver' => $driver,
        ]);
        $lock = $this->nonExpiredLockByBob();
        $didGainLock = $sut->gainLock($lock);

        $this->assertTrue($didGainLock);
        $this->assertEquals($lock, $driver->read());
    }

    public function test_it_gains_lock_if_current_lock_has_not_expired_but_was_locked_by_same_actor()
    {
        $driver = new InMemoryLockDriver($this->nonExpiredLockByAlice());
        $sut = $this->sut([
            'driver' => $driver,
        ]);
        $lock = $this->nonExpiredLockByAlice();
        $didGainLock = $sut->gainLock($lock);

        $this->assertTrue($didGainLock);
        $this->assertEquals($lock, $driver->read());
    }

    public function test_it_does_not_gain_lock_if_current_lock_has_not_expired_and_was_locked_by_different_actor()
    {
        $initialLock = $this->nonExpiredLockByAlice();
        $driver = new InMemoryLockDriver($initialLock);
        $sut = $this->sut([
            'driver' => $driver,
        ]);
        $lock = $this->nonExpiredLockByBob();
        $didGainLock = $sut->gainLock($lock);

        $this->assertFalse($didGainLock);
        $this->assertEquals($initialLock, $driver->read());
    }

    public function test_it_gains_lock_then_executes_protected_callback_and_then_releases_lock_when_it_can_gain_lock()
    {
        $callbackGain = null;
        $callbackFail = null;

        $lockUntil = $this->laterTime();
        $driver = new InMemoryLockDriver(Lock::null());
        $sut = $this->sut([
            'driver' => $driver,
            'maxAttempts' => 3,
        ]);
        $sut->protect(
            'alice',
            $lockUntil,
            function (LockGained $result) use (&$callbackGain) {
                $callbackGain = $result;
            },
            function (LockFailed $result) use (&$callbackFail) {
                $callbackFail = $result;
            }
        );

        $this->assertEquals(new LockGained(1, 3, 0), $callbackGain);
        $this->assertNull($callbackFail, 'Failure callback was called on success.');
        $this->assertEquals(Lock::null(), $driver->read(), 'Lock not released.');
    }

    public function test_it_doesnt_gain_lock_and_doesnt_execute_protected_callback_when_it_cant_gain_lock()
    {
        $callbackGain = null;
        $callbackFail = null;

        $lockUntil = $this->laterTime();
        $initialLock = $this->nonExpiredLockByBob();
        $driver = new InMemoryLockDriver($initialLock);
        $sut = $this->sut([
            'driver' => $driver,
            'maxAttempts' => 7,
        ]);

        $sut->protect(
            'alice',
            $lockUntil,
            function (LockGained $result) use (&$callbackGain) {
                $callbackGain = $result;
            },
            function (LockFailed $result) use (&$callbackFail) {
                $callbackFail = $result;
            }
        );

        $this->assertEquals(new LockFailed(7, 0), $callbackFail);
        $this->assertNull($callbackGain, 'Success callback was called on failure.');
        $this->assertEquals($initialLock, $driver->read(), 'Lock was modified.');
    }

    public function test_callback_is_made_on_each_failed_attempt_to_get_a_lock()
    {
        $callbackAttempts = [];

        $lockUntil = $this->laterTime();
        $initialLock = $this->nonExpiredLockByAlice();
        $driver = new InMemoryLockDriver($initialLock);
        $sut = $this->sut([
            'driver' => $driver,
            'maxAttempts' => 5,
        ]);

        $sut->protect(
            'bob',
            $lockUntil,
            function (LockGained $result) use (&$callbackGain) {
            },
            function (LockFailed $result) use (&$callbackFail) {
            },
            function (LockAttempt $result) use (&$callbackAttempts) {
                $callbackAttempts[] = $result;
            }
        );

        $this->assertEquals(4, count($callbackAttempts));
        $this->assertEquals([
            new LockAttempt(1, 5, 0, 0),
            new LockAttempt(2, 5, 0, 0),
            new LockAttempt(3, 5, 0, 0),
            new LockAttempt(4, 5, 0, 0),
        ], $callbackAttempts);
    }
}

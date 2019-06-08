<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\Guard;

use ChrisHarrison\Clock\FrozenClock;
use ChrisHarrison\Lock\Lock;
use ChrisHarrison\Lock\LockDriver\InMemoryLockDriver;
use ChrisHarrison\Lock\LockInspector\DefaultLockInspector;
use const DATE_RFC3339;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class LockGuardTest extends TestCase
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
            'driver' => $options['driver'] ?? new InMemoryLockDriver(Lock::null())
        ];

        return new LockGuard(
            1,
            0,
            $useOptions['driver'],
            new DefaultLockInspector(new FrozenClock($this->now()))
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
        $flag = false;
        $lockUntil = $this->laterTime();
        $driver = new InMemoryLockDriver(Lock::null());
        $sut = $this->sut([
            'driver' => $driver,
        ]);
        $sut->protect('alice', $lockUntil, function () use (&$flag) {
            $flag = true;
        });
        $this->assertTrue($flag);
        $this->assertEquals(Lock::null(), $driver->read());
    }

    public function test_it_doesnt_gain_lock_and_doesnt_execute_protected_callback_when_it_cant_gain_lock()
    {
        $flag = false;
        $lockUntil = $this->laterTime();
        $initialLock = $this->nonExpiredLockByBob();
        $driver = new InMemoryLockDriver($initialLock);
        $sut = $this->sut([
            'driver' => $driver,
        ]);
        $sut->protect('alice', $lockUntil, function () use (&$flag) {
            $flag = true;
        });
        $this->assertFalse($flag);
        $this->assertEquals($initialLock, $driver->read());
    }
}

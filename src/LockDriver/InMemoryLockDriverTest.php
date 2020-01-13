<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockDriver;

use ChrisHarrison\Lock\Lock;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class InMemoryLockDriverTest extends TestCase
{
    public function test_a_lock_can_be_written_to_the_in_memory_storage(): void
    {
        $lock = Lock::fromNative([
            'id' => '1',
            'actor' => 'bob',
            'until' => DateTimeImmutable::createFromFormat('U', '101')->format(DATE_RFC3339)
        ]);

        $driver = new InMemoryLockDriver();
        $driver->write($lock);

        $this->assertEquals($lock, $driver->read($lock->getId()));
    }

    public function test_two_locks_can_be_written_to_the_in_memory_storage(): void
    {
        $firstLock = Lock::fromNative([
            'id' => '1',
            'actor' => 'bob',
            'until' => DateTimeImmutable::createFromFormat('U', '101')->format(DATE_RFC3339)
        ]);

        $secondLock = Lock::fromNative([
            'id' => '2',
            'actor' => 'bob',
            'until' => DateTimeImmutable::createFromFormat('U', '101')->format(DATE_RFC3339)
        ]);

        $driver = new InMemoryLockDriver();
        $driver->write($firstLock);
        $driver->write($secondLock);

        $this->assertEquals($firstLock, $driver->read($firstLock->getId()));
        $this->assertEquals($secondLock, $driver->read($secondLock->getId()));
    }
}

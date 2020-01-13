<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockDriver;

use ChrisHarrison\Lock\Lock;
use ChrisHarrison\Lock\LockSerialiser\DefaultLockSerialiser;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class FilesystemLockDriverTest extends TestCase
{
    public function test_a_lock_can_be_written_to_the_filesystem(): void
    {
        $driver = new FilesystemLockDriver(
            dirname(__FILE__),
            new DefaultLockSerialiser()
        );

        $lock = Lock::fromNative([
            'id' => '1',
            'actor' => 'bob',
            'until' => DateTimeImmutable::createFromFormat('U', '101')->format(DATE_RFC3339)
        ]);

        $driver->write($lock);

        $this->assertEquals($lock, $driver->read($lock->getId()));

        $driver->delete($lock->getId());
    }

    public function test_multiple_locks_can_be_written_to_the_filesystem(): void
    {
        $driver = new FilesystemLockDriver(
            dirname(__FILE__),
            new DefaultLockSerialiser()
        );

        $firstLock = Lock::fromNative([
            'id' => '1',
            'actor' => 'bob',
            'until' => DateTimeImmutable::createFromFormat('U', '101')->format(DATE_RFC3339)
        ]);

        $driver->write($firstLock);

        $secondLock = Lock::fromNative([
            'id' => '2',
            'actor' => 'bob',
            'until' => DateTimeImmutable::createFromFormat('U', '101')->format(DATE_RFC3339)
        ]);

        $driver->write($secondLock);

        $this->assertEquals($firstLock, $driver->read($firstLock->getId()));
        $this->assertEquals($secondLock, $driver->read($secondLock->getId()));

        $driver->delete($firstLock->getId());
        $driver->delete($secondLock->getId());
    }
}

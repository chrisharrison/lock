<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockInspector;

use ChrisHarrison\Clock\FrozenClock;
use ChrisHarrison\Clock\SystemClock;
use ChrisHarrison\Lock\Lock;
use const DATE_RFC3339;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DefaultLockInspectorTest extends TestCase
{
    public function test_hasExpired_returns_true_when_lock_has_expired()
    {
        $lockTime = DateTimeImmutable::createFromFormat(DATE_RFC3339, '2010-01-01T10:00:00+00:00');
        $now = DateTimeImmutable::createFromFormat(DATE_RFC3339, '2010-01-01T10:00:01+00:00');

        $lock = Lock::fromNative([
            'actor' => 'alice',
            'until' => $lockTime->format(DATE_RFC3339),
        ]);

        $sut = new DefaultLockInspector(new FrozenClock($now));
        $this->assertTrue($sut->hasExpired($lock));
    }

    public function test_hasExpired_returns_false_when_lock_hasnt_expired()
    {
        $lockTime = DateTimeImmutable::createFromFormat(DATE_RFC3339, '2010-01-01T10:00:00+00:00');
        $now = DateTimeImmutable::createFromFormat(DATE_RFC3339, '2010-01-01T09:59:59+00:00');

        $lock = Lock::fromNative([
            'actor' => 'alice',
            'until' => $lockTime->format(DATE_RFC3339),
        ]);

        $sut = new DefaultLockInspector(new FrozenClock($now));
        $this->assertFalse($sut->hasExpired($lock));
    }

    public function test_wasLockedBy_returns_true_when_actor_is_the_same_as_the_one_in_the_lock()
    {
        $lock = Lock::fromNative([
            'actor' => 'alice',
            'until' => (new DateTimeImmutable)->format(DATE_RFC3339),
        ]);

        $sut = new DefaultLockInspector(new SystemClock);
        $this->assertTrue($sut->wasLockedBy($lock, 'alice'));
    }

    public function test_wasLockedBy_returns_false_when_actor_isnt_the_same_as_the_one_in_the_lock()
    {
        $lock = Lock::fromNative([
            'actor' => 'alice',
            'until' => (new DateTimeImmutable)->format(DATE_RFC3339),
        ]);

        $sut = new DefaultLockInspector(new SystemClock);
        $this->assertFalse($sut->wasLockedBy($lock, 'bob'));
    }
}

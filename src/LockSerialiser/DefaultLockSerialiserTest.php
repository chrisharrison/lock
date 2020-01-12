<?php

// @codingStandardsIgnoreFile

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockSerialiser;

use ChrisHarrison\Lock\Lock;
use const DATE_RFC3339;
use DateTimeImmutable;
use function json_encode;
use PHPUnit\Framework\TestCase;

final class DefaultLockSerialiserTest extends TestCase
{
    public function test_it_serialises_lock()
    {
        $time = (new DateTimeImmutable)->format(DATE_RFC3339);

        $native = [
            'id' => '123',
            'actor' => 'alice',
            'until' => $time,
        ];

        $lock = Lock::fromNative($native);

        $sut = new DefaultLockSerialiser;
        $this->assertEquals(json_encode($native), $sut->serialise($lock));
    }

    public function test_it_unserialises_lock()
    {
        $time = (new DateTimeImmutable)->format(DATE_RFC3339);

        $native = [
            'id' => '145',
            'actor' => 'alice',
            'until' => $time,
        ];

        $lock = Lock::fromNative($native);

        $sut = new DefaultLockSerialiser;
        $this->assertEquals($lock, $sut->unserialise(json_encode($native)));
    }
}

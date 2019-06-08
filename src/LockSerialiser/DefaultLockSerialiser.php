<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockSerialiser;

use ChrisHarrison\Lock\Lock;

final class DefaultLockSerialiser implements LockSerialiser
{
    public function serialise(Lock $lock): string
    {
        return json_encode($lock->toNative());
    }

    public function unserialise(string $lock): Lock
    {
        return Lock::fromNative(json_decode($lock, true));
    }
}

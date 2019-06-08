<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockSerialiser;

use ChrisHarrison\Lock\Lock;

interface LockSerialiser
{
    public function serialise(Lock $lock): string;
    public function unserialise(string $lock): Lock;
}

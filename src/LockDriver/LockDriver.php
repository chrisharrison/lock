<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockDriver;

use ChrisHarrison\Lock\Lock;

interface LockDriver
{
    public function read(): Lock;
    public function write(Lock $lock): void;
}

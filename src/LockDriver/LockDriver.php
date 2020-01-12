<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock\LockDriver;

use ChrisHarrison\Lock\Lock;

interface LockDriver
{
    public function read(string $id): Lock;
    public function write(Lock $lock): void;
    public function delete(string $id): void;
}

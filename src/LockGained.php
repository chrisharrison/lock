<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock;

final class LockGained
{
    private $attemptsMade;
    private $maxAttempts;
    private $timeTaken;

    public function __construct(
        int $attemptsMade,
        int $maxAttempts,
        int $timeTaken
    ) {
        $this->attemptsMade = $attemptsMade;
        $this->maxAttempts = $maxAttempts;
        $this->timeTaken = $timeTaken;
    }

    public function getAttemptsMade(): int
    {
        return $this->attemptsMade;
    }

    public function getMaxAttempts(): int
    {
        return $this->maxAttempts;
    }

    public function getTimeTaken(): int
    {
        return $this->timeTaken;
    }
}

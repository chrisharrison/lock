<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock;

final class LockAttempt
{
    private $attemptsMade;
    private $maxAttempts;
    private $timeTaken;
    private $secondsUntilNextAttempt;

    public function __construct(
        int $attemptsMade,
        int $maxAttempts,
        int $timeTaken,
        int $secondsUntilNextAttempt
    ) {
        $this->attemptsMade = $attemptsMade;
        $this->maxAttempts = $maxAttempts;
        $this->timeTaken = $timeTaken;
        $this->secondsUntilNextAttempt = $secondsUntilNextAttempt;
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

    public function getSecondsUntilNextAttempt(): int
    {
        return $this->secondsUntilNextAttempt;
    }
}

<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock;

final class LockFailed
{
    private $attemptsMade;
    private $timeTaken;

    public function __construct(
        int $attemptsMade,
        int $timeTaken
    ) {
        $this->attemptsMade = $attemptsMade;
        $this->timeTaken = $timeTaken;
    }

    public function getAttemptsMade(): int
    {
        return $this->attemptsMade;
    }

    public function getTimeTaken(): int
    {
        return $this->timeTaken;
    }
}

<?php

declare(strict_types=1);

namespace ChrisHarrison\Lock;

use DateTimeImmutable;
use DateTimeInterface;

final class Lock
{
    private $isNull;
    private $actor;
    private $until;

    protected function __construct(
        bool $isNull,
        ?string $actor = null,
        ?DateTimeInterface $until = null
    ) {
        $this->isNull = $isNull;
        $this->actor = $actor;
        $this->until = $until;
    }

    public function getActor(): ?string
    {
        return $this->actor;
    }

    public function getUntil(): ?DateTimeInterface
    {
        return $this->until;
    }

    public function isNull(): bool
    {
        return $this->isNull;
    }

    public function isNotNull(): bool
    {
        return !$this->isNull();
    }

    public static function fromNative(?array $native): self
    {
        if ($native === null) {
            return new self(true);
        }
        return new self(
            false,
            $native['actor'],
            DateTimeImmutable::createFromFormat(DATE_RFC3339, $native['until'])
        );
    }

    public function toNative(): ?array
    {
        if ($this->isNull) {
            return null;
        }
        return [
            'actor' => $this->actor,
            'until' => $this->until->format(DATE_RFC3339),
        ];
    }

    public static function null(): self
    {
        return self::fromNative(null);
    }
}

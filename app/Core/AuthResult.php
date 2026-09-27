<?php

declare(strict_types=1);

namespace Garage\Core;

/** Outcome of an owner-password check. */
final class AuthResult
{
    public const OK = 'ok';
    public const INVALID = 'invalid';
    public const LOCKED = 'locked';
    public const MISSING = 'missing';

    private function __construct(public readonly string $status, public readonly int $retryAfter = 0)
    {
    }

    public static function ok(): self
    {
        return new self(self::OK);
    }

    public static function invalid(): self
    {
        return new self(self::INVALID);
    }

    public static function missing(): self
    {
        return new self(self::MISSING);
    }

    public static function locked(int $seconds): self
    {
        return new self(self::LOCKED, $seconds);
    }

    public function passed(): bool
    {
        return $this->status === self::OK;
    }

    /** Message for the visitor, in the current language. */
    public function message(): string
    {
        return match ($this->status) {
            self::OK => '',
            self::LOCKED => t('auth.locked', ['minutes' => (string) max(1, (int) ceil($this->retryAfter / 60))]),
            self::MISSING => t('auth.missing'),
            default => t('auth.invalid'),
        };
    }
}

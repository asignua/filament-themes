<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Support;

/**
 * What is stored for one user (or for the whole panel): a theme name and a primary colour, both optional.
 */
final readonly class ThemeChoice
{
    public function __construct(
        public ?string $theme = null,
        public ?string $color = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->theme === null && $this->color === null;
    }
}

<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Tests\Feature;

use Asignua\FilamentThemes\Contracts\Theme;

/**
 * A bare-minimum custom theme (the hasnayeen/themes contract): fixed colours, no stylesheet.
 */
class FixedTheme implements Theme
{
    public static function getName(): string
    {
        return 'fixed';
    }

    public static function getPath(): ?string
    {
        return null;
    }

    public function getThemeColor(): array
    {
        return ['primary' => '#cc0066', 'danger' => '#990000'];
    }
}

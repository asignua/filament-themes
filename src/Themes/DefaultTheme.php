<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Themes;

use Asignua\FilamentThemes\Contracts\HasChangeableColor;
use Asignua\FilamentThemes\Contracts\Theme;
use Filament\Support\Contracts\HasLabel;

/**
 * No overrides: the panel looks exactly as the panel provider configured it.
 */
class DefaultTheme implements HasChangeableColor, HasLabel, Theme
{
    public static function getName(): string
    {
        return 'default';
    }

    public static function getPath(): ?string
    {
        return null;
    }

    public function getLabel(): string
    {
        return __('filament-themes::filament-themes.themes.default');
    }

    public function getThemeColor(): array
    {
        return [];
    }

    public function getPrimaryColor(): array
    {
        return [];
    }
}

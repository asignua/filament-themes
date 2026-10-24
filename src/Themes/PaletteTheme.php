<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Themes;

use Asignua\FilamentThemes\Contracts\HasChangeableColor;
use Asignua\FilamentThemes\Contracts\Theme;
use Asignua\FilamentThemes\Support\Palette;
use Filament\Support\Contracts\HasLabel;

/**
 * Base of the built-in themes: every colour is a [hue, chroma] pair turned into a shade ladder by `Palette`,
 * so contrast is guaranteed by construction. Extend it for your own theme, or implement `Theme` directly.
 */
abstract class PaletteTheme implements HasChangeableColor, HasLabel, Theme
{
    /**
     * Hue (0-360) and chroma (0-0.2) per colour name. `gray` is the neutral ladder, the rest are accents.
     *
     * @return array<string, array{0: float|int, 1: float|int}>
     */
    abstract protected function hues(): array;

    public static function getPath(): ?string
    {
        return null;
    }

    public function getLabel(): string
    {
        $key = 'filament-themes::filament-themes.themes.'.static::getName();
        $label = __($key);

        return $label === $key ? ucfirst(static::getName()) : $label;
    }

    public function getThemeColor(): array
    {
        $colors = [];

        foreach ($this->hues() as $name => [$hue, $chroma]) {
            $colors[$name] = $name === 'gray' ? Palette::gray($hue, $chroma) : Palette::accent($hue, $chroma);
        }

        return $colors;
    }

    public function getPrimaryColor(): array
    {
        $primary = $this->getThemeColor()['primary'] ?? [];

        return is_array($primary) ? $primary : [];
    }
}

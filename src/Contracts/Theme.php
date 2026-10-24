<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Contracts;

/**
 * A theme: a unique name, an optional stylesheet and colour overrides.
 *
 * Same three methods as `Hasnayeen\Themes\Contracts\Theme`, so an existing theme class only needs a new `use`.
 * Optional extras: `Filament\Support\Contracts\HasLabel` (card title), `HasChangeableColor`,
 * `CanModifyPanelConfig`, `HasOnlyDarkMode`, `HasOnlyLightMode`.
 */
interface Theme
{
    /**
     * The stored identifier, e.g. `nord`. Lower-case letters, digits, `-` and `_`.
     */
    public static function getName(): string;

    /**
     * Absolute path of an extra stylesheet, or null when the theme only changes colours.
     */
    public static function getPath(): ?string;

    /**
     * Colour overrides by Filament colour name (`primary`, `gray`, `danger`, ...). Each value is a hex / rgb
     * string or a full shade palette (50..950).
     *
     * @return array<string, array<int|string, string>|string>
     */
    public function getThemeColor(): array;
}

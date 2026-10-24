<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Support;

use Filament\Support\Colors\Color;
use InvalidArgumentException;

/**
 * Shade palettes (50..950) in OKLCH from a hue and a chroma. The lightness ladder is fixed, which is what makes
 * the contrast guarantees: shade 600 always carries white text at WCAG AA (4.5:1) and the gray ladder keeps its
 * body-text pairs above AA in light and dark mode (both are covered by tests).
 */
final class Palette
{
    /** Accent shades: 600 is the solid button background (white text), 500 its dark-mode hover. */
    private const array ACCENT_LIGHTNESS = [
        50 => 0.975, 100 => 0.95, 200 => 0.9, 300 => 0.83, 400 => 0.74, 500 => 0.64,
        600 => 0.54, 700 => 0.46, 800 => 0.39, 900 => 0.33, 950 => 0.25,
    ];

    /** Share of the base chroma used by each accent shade. */
    private const array ACCENT_CHROMA = [
        50 => 0.1, 100 => 0.22, 200 => 0.42, 300 => 0.68, 400 => 0.9, 500 => 1.0,
        600 => 1.0, 700 => 0.9, 800 => 0.75, 900 => 0.6, 950 => 0.45,
    ];

    /** Neutral shades: text 950/500 on 50, white on 900, 400 on 900/950. */
    private const array GRAY_LIGHTNESS = [
        50 => 0.985, 100 => 0.965, 200 => 0.925, 300 => 0.87, 400 => 0.7, 500 => 0.54,
        600 => 0.44, 700 => 0.37, 800 => 0.27, 900 => 0.21, 950 => 0.145,
    ];

    /**
     * @return array<int, string>
     */
    public static function accent(float $hue, float $chroma): array
    {
        $palette = [];

        foreach (self::ACCENT_LIGHTNESS as $shade => $lightness) {
            $palette[$shade] = self::oklch($lightness, $chroma * self::ACCENT_CHROMA[$shade], $hue);
        }

        return $palette;
    }

    /**
     * A tinted neutral ladder: the surfaces of the panel (backgrounds, borders, text) take the tint.
     *
     * @return array<int, string>
     */
    public static function gray(float $hue, float $chroma): array
    {
        $palette = [];

        foreach (self::GRAY_LIGHTNESS as $shade => $lightness) {
            $palette[$shade] = self::oklch($lightness, $chroma, $hue);
        }

        return $palette;
    }

    /**
     * An accent palette built from the hue and chroma of any hex colour (chroma is capped, so a neon colour
     * cannot break the contrast of the ladder).
     *
     * @return array<int, string>
     */
    public static function fromHex(string $hex): array
    {
        if (!self::isHex($hex)) {
            throw new InvalidArgumentException("[{$hex}] is not a #rrggbb colour.");
        }

        $oklch = Color::convertToOklch(strtolower($hex));

        if (sscanf($oklch, 'oklch(%f %f %f)', $lightness, $chroma, $hue) !== 3) {
            throw new InvalidArgumentException("Cannot read [{$hex}] as a colour.");
        }

        return self::accent((float) $hue, min((float) $chroma, 0.19));
    }

    public static function isHex(string $value): bool
    {
        return preg_match('/^#[0-9a-fA-F]{6}$/', $value) === 1;
    }

    private static function oklch(float $lightness, float $chroma, float $hue): string
    {
        return sprintf('oklch(%s %s %s)', round($lightness, 4), round($chroma, 4), round($hue, 2));
    }
}

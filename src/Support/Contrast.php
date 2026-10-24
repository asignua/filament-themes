<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Support;

use InvalidArgumentException;

/**
 * WCAG 2.x contrast for the colour formats Filament palettes use (`#rrggbb`, `#rgb`, `oklch(L C H)`).
 * An out-of-gamut OKLCH colour is mapped the way browsers do it: chroma is reduced, lightness is kept.
 */
final class Contrast
{
    /** @var array<string, float> */
    private static array $cache = [];

    public static function ratio(string $foreground, string $background): float
    {
        $a = self::luminance($foreground);
        $b = self::luminance($background);

        return (max($a, $b) + 0.05) / (min($a, $b) + 0.05);
    }

    public static function luminance(string $color): float
    {
        if (isset(self::$cache[$color])) {
            return self::$cache[$color];
        }

        [$r, $g, $b] = self::linearRgb($color);

        return self::$cache[$color] = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    }

    /**
     * @return array{0: float, 1: float, 2: float} linear sRGB, each 0..1
     */
    public static function linearRgb(string $color): array
    {
        $color = strtolower(trim($color));

        if (str_starts_with($color, '#')) {
            $hex = substr($color, 1);

            if (strlen($hex) === 3) {
                $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
            }

            if (!preg_match('/^[0-9a-f]{6}$/', $hex)) {
                throw new InvalidArgumentException("Unsupported colour [{$color}].");
            }

            return [
                self::toLinear(hexdec(substr($hex, 0, 2)) / 255),
                self::toLinear(hexdec(substr($hex, 2, 2)) / 255),
                self::toLinear(hexdec(substr($hex, 4, 2)) / 255),
            ];
        }

        if (preg_match('/^oklch\(\s*([\d.]+)(%?)\s+([\d.]+)\s+([\d.]+)(?:deg)?\s*\)$/', $color, $m)) {
            $lightness = (float) $m[1] / ($m[2] === '%' ? 100 : 1);

            return self::oklchToLinear($lightness, (float) $m[3], (float) $m[4]);
        }

        throw new InvalidArgumentException("Unsupported colour [{$color}].");
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function oklchToLinear(float $lightness, float $chroma, float $hue): array
    {
        $low = 0.0;
        $high = 1.0;
        $best = self::oklabToLinear($lightness, 0.0, 0.0);

        if (self::inGamut($rgb = self::oklabToLinear($lightness, $chroma * cos(deg2rad($hue)), $chroma * sin(deg2rad($hue))))) {
            return self::clamp($rgb);
        }

        for ($i = 0; $i < 24; $i++) {
            $mid = ($low + $high) / 2;
            $rgb = self::oklabToLinear($lightness, $mid * $chroma * cos(deg2rad($hue)), $mid * $chroma * sin(deg2rad($hue)));

            if (self::inGamut($rgb)) {
                $low = $mid;
                $best = $rgb;
            } else {
                $high = $mid;
            }
        }

        return self::clamp($best);
    }

    /**
     * @return array{0: float, 1: float, 2: float}
     */
    private static function oklabToLinear(float $lightness, float $a, float $b): array
    {
        $l = ($lightness + 0.3963377774 * $a + 0.2158037573 * $b) ** 3;
        $m = ($lightness - 0.1055613458 * $a - 0.0638541728 * $b) ** 3;
        $s = ($lightness - 0.0894841775 * $a - 1.2914855480 * $b) ** 3;

        return [
            4.0767416621 * $l - 3.3077115913 * $m + 0.2309699292 * $s,
            -1.2684380046 * $l + 2.6097574011 * $m - 0.3413193965 * $s,
            -0.0041960863 * $l - 0.7034186147 * $m + 1.7076147010 * $s,
        ];
    }

    /**
     * @param array{0: float, 1: float, 2: float} $rgb
     */
    private static function inGamut(array $rgb): bool
    {
        foreach ($rgb as $channel) {
            if ($channel < -0.0001 || $channel > 1.0001) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array{0: float, 1: float, 2: float} $rgb
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private static function clamp(array $rgb): array
    {
        return [max(0.0, min(1.0, $rgb[0])), max(0.0, min(1.0, $rgb[1])), max(0.0, min(1.0, $rgb[2]))];
    }

    private static function toLinear(float $channel): float
    {
        return $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4;
    }
}

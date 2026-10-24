<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Tests\Unit;

use Asignua\FilamentThemes\Support\Contrast;
use Asignua\FilamentThemes\Support\Palette;
use Asignua\FilamentThemes\ThemesPlugin;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * WCAG AA (4.5:1) for what a theme paints: the text on a solid primary button, the text of the neutral ladder in
 * light and dark mode, and the same for any primary colour a user may type in.
 */
class ContrastTest extends TestCase
{
    private const float AA = 4.5;

    public function test_the_ratio_matches_the_wcag_reference_values(): void
    {
        $this->assertEqualsWithDelta(21.0, Contrast::ratio('#000000', '#ffffff'), 0.001);
        $this->assertEqualsWithDelta(1.0, Contrast::ratio('#336699', '#336699'), 0.001);
        // #767676 on white is the textbook "just passes AA" gray (4.54:1).
        $this->assertEqualsWithDelta(4.54, Contrast::ratio('#767676', '#ffffff'), 0.01);
        $this->assertEqualsWithDelta(Contrast::ratio('#fff', '#000'), Contrast::ratio('oklch(1 0 0)', 'oklch(0 0 0)'), 0.001);
    }

    public function test_an_unsupported_colour_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Contrast::ratio('red', '#ffffff');
    }

    /**
     * @return iterable<string, array{0: string}>
     */
    public static function themes(): iterable
    {
        foreach (ThemesPlugin::builtIn() as $name => $class) {
            if ($name !== 'default') {
                yield $name => [$class];
            }
        }
    }

    #[DataProvider('themes')]
    public function test_white_text_on_a_solid_button_passes_aa_for_every_accent(string $class): void
    {
        foreach ((new $class)->getThemeColor() as $name => $palette) {
            if ($name === 'gray') {
                continue;
            }

            $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio('#ffffff', $palette[600]), "{$class} {$name}-600 vs white");
        }
    }

    #[DataProvider('themes')]
    public function test_text_on_the_neutral_surfaces_passes_aa_in_light_and_dark_mode(string $class): void
    {
        $gray = (new $class)->getThemeColor()['gray'];

        // Light: body text and muted text on the page background and on white cards.
        $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio($gray[950], $gray[50]));
        $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio($gray[500], $gray[50]));
        $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio($gray[500], '#ffffff'));
        // Dark: white text and muted text on the page (950) and on cards (900, 800).
        $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio('#ffffff', $gray[950]));
        $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio($gray[400], $gray[950]));
        $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio($gray[400], $gray[900]));
        $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio($gray[400], $gray[800]));
    }

    #[DataProvider('themes')]
    public function test_the_primary_stays_readable_as_text_and_as_a_link(string $class): void
    {
        $primary = (new $class)->getThemeColor()['primary'];
        $gray = (new $class)->getThemeColor()['gray'];

        // Light mode links are primary-600 on white; dark mode links are primary-400 on gray-900.
        $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio($primary[600], '#ffffff'));
        $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio($primary[400], $gray[900]));
    }

    public function test_a_custom_hex_colour_gets_an_aa_button_whatever_its_hue(): void
    {
        for ($hue = 0; $hue < 360; $hue += 15) {
            foreach ([0.04, 0.12, 0.2] as $chroma) {
                $palette = Palette::accent((float) $hue, $chroma);

                $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio('#ffffff', $palette[600]), "hue {$hue} chroma {$chroma}");
            }
        }

        foreach (['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#ff00ff', '#00ffff', '#ff8800', '#808080', '#ffffff', '#000000'] as $hex) {
            $this->assertGreaterThanOrEqual(self::AA, Contrast::ratio('#ffffff', Palette::fromHex($hex)[600]), $hex);
        }
    }
}

<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Tests\Feature;

use Asignua\FilamentThemes\Support\Contrast;
use Asignua\FilamentThemes\Support\Palette;
use Asignua\FilamentThemes\Tests\TestCase;
use Asignua\FilamentThemes\ThemesPlugin;
use Filament\Support\View\Components\ButtonComponent;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Not contrast math on a hand-picked shade: what Filament ITSELF picks for a solid button of the registered
 * palette (shade for the background, shade for the text, hover included). A palette whose 500 allows dark text
 * but not white one makes Filament fall back to a pale 400 button with dark text.
 */
class FilamentButtonPickTest extends TestCase
{
    /**
     * @return iterable<string, array{0: array<int, string>}>
     */
    public static function palettes(): iterable
    {
        foreach (ThemesPlugin::builtIn() as $name => $class) {
            if ($name === 'default') {
                continue;
            }

            foreach ((new $class)->getThemeColor() as $color => $palette) {
                if ($color !== 'gray') {
                    yield "{$name} {$color}" => [$palette];
                }
            }
        }

        foreach (['#ff0000', '#00ff00', '#0000ff', '#ffff00', '#aa3366', '#112233', '#ff8800'] as $hex) {
            yield "custom {$hex}" => [Palette::fromHex($hex)];
        }
    }

    /**
     * @param array<int, string> $palette
     */
    #[DataProvider('palettes')]
    public function test_filament_renders_a_solid_600_button_with_light_text_in_both_modes(array $palette): void
    {
        $map = ButtonComponent::make()->getColorMap($palette);

        $this->assertSame(600, $map['bg']);
        $this->assertSame(600, $map['dark:bg']);
        $this->assertSame(500, $map['hover:bg']);

        // Text shade 0 = white, 50 = the palest shade: both are "light text".
        foreach (['text', 'hover:text', 'dark:text', 'dark:hover:text'] as $key) {
            $this->assertContains($map[$key], [0, 50], "{$key} must be light text.");
        }

        $text = fn (int $shade): string => $shade === 0 ? '#ffffff' : $palette[$shade];

        $this->assertGreaterThanOrEqual(4.5, Contrast::ratio($text($map['text']), $palette[$map['bg']]), 'rest');
        $this->assertGreaterThanOrEqual(4.5, Contrast::ratio($text($map['hover:text']), $palette[$map['hover:bg']]), 'hover');
    }
}

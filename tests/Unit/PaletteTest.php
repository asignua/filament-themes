<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Tests\Unit;

use Asignua\FilamentThemes\Support\Palette;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PaletteTest extends TestCase
{
    public function test_a_palette_has_every_shade_in_order_of_lightness(): void
    {
        foreach ([Palette::accent(250, 0.1), Palette::gray(250, 0.02)] as $palette) {
            $this->assertSame([50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950], array_keys($palette));

            $lightness = array_values(array_map(fn (string $c): float => (float) sscanf($c, 'oklch(%f')[0], $palette));
            $sorted = $lightness;
            rsort($sorted);

            $this->assertSame($sorted, $lightness);
        }
    }

    public function test_from_hex_keeps_the_hue_of_the_colour(): void
    {
        $this->assertStringContainsString(' 29.', Palette::fromHex('#ff0000')[500]);
        $this->assertSame(Palette::fromHex('#336699'), Palette::fromHex('#336699'));
    }

    public function test_hex_detection_is_strict(): void
    {
        $this->assertTrue(Palette::isHex('#a1B2c3'));
        $this->assertFalse(Palette::isHex('#abc'));
        $this->assertFalse(Palette::isHex('a1b2c3'));
        $this->assertFalse(Palette::isHex('#a1b2c3;x'));
    }

    public function test_from_hex_rejects_other_formats(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Palette::fromHex('blue');
    }
}

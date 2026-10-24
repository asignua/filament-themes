<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Tests\Feature;

use Asignua\FilamentThemes\Tests\TestCase;
use Asignua\FilamentThemes\ThemesPlugin;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentAsset;

class SmokeTest extends TestCase
{
    public function test_the_plugin_is_registered_on_the_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasPlugin(ThemesPlugin::ID));
        $this->assertInstanceOf(ThemesPlugin::class, $panel->getPlugin(ThemesPlugin::ID));
    }

    public function test_six_themes_are_built_in(): void
    {
        $this->assertSame(['default', 'nord', 'dracula', 'sunset', 'ocean', 'forest'], array_keys(ThemesPlugin::builtIn()));
    }

    public function test_the_stylesheet_is_registered_as_a_filament_asset(): void
    {
        $ids = array_map(fn ($asset) => $asset->getId(), FilamentAsset::getStyles(['asignua/filament-themes']));

        $this->assertSame(['filament-themes'], $ids);
        $this->assertStringStartsNotWith('@layer', trim((string) file_get_contents(__DIR__.'/../../resources/dist/filament-themes.css')));
    }

    public function test_every_built_in_theme_has_a_label_and_a_description(): void
    {
        foreach (array_keys(ThemesPlugin::builtIn()) as $name) {
            $this->assertNotSame('filament-themes::filament-themes.themes.'.$name, __('filament-themes::filament-themes.themes.'.$name));
            $this->assertNotSame('filament-themes::filament-themes.descriptions.'.$name, __('filament-themes::filament-themes.descriptions.'.$name));
        }
    }
}

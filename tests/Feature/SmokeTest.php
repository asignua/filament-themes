<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Tests\Feature;

use Asignua\FilamentThemes\ThemesPlugin;
use Asignua\FilamentThemes\Tests\TestCase;
use Filament\Facades\Filament;

class SmokeTest extends TestCase
{
    public function test_the_panel_boots(): void
    {
        $this->assertSame('admin', Filament::getCurrentPanel()?->getId());
    }

    public function test_the_plugin_is_registered_on_the_panel(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue($panel->hasPlugin('asignua-filament-themes'));
        $this->assertInstanceOf(ThemesPlugin::class, $panel->getPlugin('asignua-filament-themes'));
    }

    public function test_the_translations_are_loaded(): void
    {
        $this->assertSame('Sample', __('filament-themes::filament-themes.sample'));
    }
}

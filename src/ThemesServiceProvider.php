<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes;

use Asignua\FilamentThemes\Commands\InstallCommand;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ThemesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-themes';

    public function configurePackage(Package $package): void
    {
        // Translations: `__('filament-themes::filament-themes.<key>')`, publish tag `filament-themes-translations`.
        // Config: publish tag `filament-themes-config`. Migration: tag `filament-themes-migrations`.
        $package->name(static::$name)
            ->hasConfigFile()
            ->hasTranslations()
            ->hasViews()
            ->hasMigration('create_filament_themes_table')
            ->hasCommand(InstallCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(ThemeStore::class);
    }

    public function packageBooted(): void
    {
        FilamentAsset::register([
            Css::make('filament-themes', __DIR__.'/../resources/dist/filament-themes.css'),
        ], 'asignua/filament-themes');
    }
}

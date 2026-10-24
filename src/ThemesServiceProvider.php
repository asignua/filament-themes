<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ThemesServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-themes';

    public function configurePackage(Package $package): void
    {
        // Translations live in resources/lang/<locale>/filament-themes.php and are read as
        // `__('filament-themes::filament-themes.<key>')`. Publish tag: `filament-themes-translations`.
        $package->name(static::$name)
            ->hasTranslations()
            ->hasViews();

        // Add a config file only when the plugin really has options: create config/filament-themes.php and
        // chain `->hasConfigFile()` here (publish tag `filament-themes-config`). Prefer fluent setters on the Plugin.
    }
}

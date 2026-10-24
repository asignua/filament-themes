<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'filament-themes:install';

    protected $description = 'Publish the Filament assets (the plugin stylesheet) and the settings migration.';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => 'filament-themes-migrations']);

        return $this->call('filament:assets');
    }
}

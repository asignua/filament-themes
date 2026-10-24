<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Contracts;

use Filament\Panel;

/**
 * The theme changes the panel itself (top navigation, sidebar, ...). Runs once per request, after the session
 * is started. Under Octane the panel object outlives the request, so set values both ways (on and off).
 */
interface CanModifyPanelConfig
{
    public function modifyPanelConfig(Panel $panel): Panel;
}

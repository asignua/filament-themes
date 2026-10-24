<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Http\Middleware;

use Asignua\FilamentThemes\ThemesPlugin;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Optional. The plugin applies the theme by itself (colours, dark mode, stylesheet marker) once the panel is
 * served, so nothing has to be registered. This middleware only exists for panels migrated from hasnayeen/themes
 * and for panels whose stack lacks `DispatchServingFilamentEvent`: it runs the theme's `modifyPanelConfig()`.
 * Running it together with the plugin is harmless.
 */
class SetTheme
{
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getCurrentPanel();

        if ($panel !== null && $panel->hasPlugin(ThemesPlugin::ID)) {
            ThemesPlugin::get()->manager()->applyPanelConfig();
        }

        return $next($request);
    }
}

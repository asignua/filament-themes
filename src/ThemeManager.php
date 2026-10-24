<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes;

use Asignua\FilamentThemes\Contracts\CanModifyPanelConfig;
use Asignua\FilamentThemes\Contracts\HasChangeableColor;
use Asignua\FilamentThemes\Contracts\HasOnlyDarkMode;
use Asignua\FilamentThemes\Contracts\HasOnlyLightMode;
use Asignua\FilamentThemes\Contracts\Theme;
use Asignua\FilamentThemes\Support\Palette;
use Asignua\FilamentThemes\Support\ThemeChoice;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Colors\ColorManager;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;

/**
 * Resolves and stores the theme of ONE panel for the current request. Everything is derived from the request
 * and the store, never kept on the object, so it is safe for Octane (the panel and plugin live across requests).
 */
final class ThemeManager
{
    public function __construct(
        private readonly ThemesPlugin $plugin,
        private readonly Panel $panel,
        private readonly ThemeStore $store,
    ) {}

    public function user(): ?Authenticatable
    {
        return $this->panel->auth()->user();
    }

    public function isGlobal(): bool
    {
        return $this->plugin->getMode() === 'global';
    }

    public function choice(): ThemeChoice
    {
        $user = $this->user();
        $key = 'filament-themes.'.$this->panel->getId().'.'.($this->isGlobal() ? 'global' : ($user?->getAuthIdentifier() ?? 'guest'));
        $attributes = request()->attributes;

        if (!$attributes->has($key)) {
            $attributes->set($key, $this->store->get($this->panel->getId(), $this->isGlobal(), $user));
        }

        /** @var ThemeChoice */
        return $attributes->get($key);
    }

    public function themeName(): string
    {
        $themes = $this->plugin->getThemes();
        $chosen = $this->choice()->theme;

        if ($chosen !== null && isset($themes[$chosen])) {
            return $chosen;
        }

        return $this->defaultThemeName();
    }

    public function defaultThemeName(): string
    {
        $themes = $this->plugin->getThemes();
        $default = $this->plugin->getDefaultTheme() ?? config('filament-themes.default.theme');

        if (is_string($default) && isset($themes[$default])) {
            return $default;
        }

        return (string) array_key_first($themes);
    }

    public function theme(): Theme
    {
        $class = $this->plugin->getThemes()[$this->themeName()];

        return app($class);
    }

    public function canChangeColor(?Theme $theme = null): bool
    {
        return ($theme ?? $this->theme()) instanceof HasChangeableColor;
    }

    /**
     * The chosen (or default) primary colour: a Filament colour name or `#rrggbb`; null = the theme's own.
     */
    public function colorSetting(): ?string
    {
        if (!$this->canChangeColor()) {
            return null;
        }

        $color = $this->choice()->color ?? $this->plugin->getDefaultColor() ?? config('filament-themes.default.theme_color');

        return is_string($color) && $this->resolveColor($color) !== null ? $color : null;
    }

    /**
     * @return array<string, array<int|string, string>|string>
     */
    public function colors(): array
    {
        $theme = $this->theme();
        $colors = [];

        foreach ($theme->getThemeColor() as $name => $value) {
            $colors[$name] = is_string($value) && Palette::isHex($value) ? Palette::fromHex($value) : $value;
        }

        if ($theme instanceof HasChangeableColor) {
            $override = ($setting = $this->colorSetting()) !== null ? $this->resolveColor($setting) : null;
            $primary = $override ?? $theme->getPrimaryColor();

            if ($primary !== []) {
                $colors['primary'] = $primary;
            }
        }

        return $colors;
    }

    /**
     * A Filament colour name or a #rrggbb into a shade palette; null when it is neither (or not allowed).
     *
     * @return array<int|string, string>|null
     */
    public function resolveColor(string $color): ?array
    {
        if (Palette::isHex($color)) {
            return $this->plugin->allowsCustomColor() ? Palette::fromHex($color) : null;
        }

        if (!in_array($color, $this->plugin->getColors(), true)) {
            return null;
        }

        return Color::all()[$color] ?? null;
    }

    public function selectTheme(string $name): void
    {
        if (!isset($this->plugin->getThemes()[$name])) {
            throw ValidationException::withMessages(['theme' => __('filament-themes::filament-themes.invalid_theme')]);
        }

        $this->persist(new ThemeChoice($name, $this->choice()->color));
    }

    public function selectColor(?string $color): void
    {
        if ($color !== null && $this->resolveColor($color) === null) {
            throw ValidationException::withMessages(['color' => __('filament-themes::filament-themes.invalid_color')]);
        }

        $this->persist(new ThemeChoice($this->choice()->theme, $color === null ? null : strtolower($color)));
    }

    /**
     * Runs the theme's panel hook once per request (the SetTheme middleware and the serving event both call it).
     */
    public function applyPanelConfig(): void
    {
        $attributes = request()->attributes;
        $key = 'filament-themes.'.$this->panel->getId().'.applied';

        if ($attributes->get($key) === true) {
            return;
        }

        $attributes->set($key, true);

        $theme = $this->theme();

        if ($theme instanceof CanModifyPanelConfig) {
            $theme->modifyPanelConfig($this->panel);
        }
    }

    public function isDarkOnly(): bool
    {
        return $this->theme() instanceof HasOnlyDarkMode;
    }

    public function isLightOnly(): bool
    {
        return $this->theme() instanceof HasOnlyLightMode;
    }

    /**
     * What a theme card paints: the label and a handful of shades, for the light and the dark half.
     *
     * @return array{name: string, label: string, current: bool, changeableColor: bool, shades: array<string, string>}
     */
    public function card(string $name): array
    {
        $theme = app($this->plugin->getThemes()[$name]);
        $current = $name === $this->themeName();
        $colors = $current ? $this->colors() : $theme->getThemeColor();
        $base = ColorManager::DEFAULT_COLORS;

        $palette = function (string $color) use ($colors, $base): array {
            $value = $colors[$color] ?? $base[$color] ?? $base['gray'];

            if (is_string($value)) {
                return Palette::isHex($value) ? Palette::fromHex($value) : Color::generatePalette($value);
            }

            return array_map(fn (mixed $shade): string => (string) $shade, $value);
        };

        $label = $theme instanceof HasLabel ? $theme->getLabel() : null;

        return [
            'name' => $name,
            'label' => match (true) {
                $label === null => (string) str($name)->headline(),
                is_string($label) => $label,
                default => strip_tags($label->toHtml()),
            },
            'current' => $current,
            'changeableColor' => $theme instanceof HasChangeableColor,
            'shades' => [
                'light' => $palette('gray')[50] ?? '#f9fafb',
                'light-line' => $palette('gray')[200] ?? '#e5e7eb',
                'dark' => $palette('gray')[900] ?? '#111827',
                'dark-line' => $palette('gray')[700] ?? '#374151',
                'primary' => $palette('primary')[600] ?? '#d97706',
                'primary-soft' => $palette('primary')[300] ?? '#fcd34d',
                'success' => $palette('success')[500] ?? '#22c55e',
                'warning' => $palette('warning')[500] ?? '#f59e0b',
                'danger' => $palette('danger')[500] ?? '#ef4444',
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function cards(): array
    {
        return array_map(fn (string $name): array => $this->card($name), array_keys($this->plugin->getThemes()));
    }

    /**
     * The colour swatches of the picker: Filament colour name => its 500 shade.
     *
     * @return array<string, string>
     */
    public function swatches(): array
    {
        $swatches = [];

        foreach ($this->plugin->getColors() as $name) {
            if (isset(Color::all()[$name])) {
                $swatches[$name] = (string) (Color::all()[$name][500] ?? '');
            }
        }

        return $swatches;
    }

    private function persist(ThemeChoice $choice): void
    {
        $this->store->put($this->panel->getId(), $this->isGlobal(), $this->user(), $choice);

        $user = $this->user();
        request()->attributes->remove('filament-themes.'.$this->panel->getId().'.'.($this->isGlobal() ? 'global' : ($user?->getAuthIdentifier() ?? 'guest')));
    }
}

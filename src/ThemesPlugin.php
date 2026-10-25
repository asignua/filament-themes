<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes;

use Asignua\FilamentThemes\Contracts\Theme;
use Asignua\FilamentThemes\Pages\Themes as ThemesPage;
use Asignua\FilamentThemes\Support\Palette;
use Asignua\FilamentThemes\Themes\DefaultTheme;
use Asignua\FilamentThemes\Themes\Dracula;
use Asignua\FilamentThemes\Themes\Forest;
use Asignua\FilamentThemes\Themes\Nord;
use Asignua\FilamentThemes\Themes\Ocean;
use Asignua\FilamentThemes\Themes\Sunset;
use Closure;
use Filament\Actions\Action;
use Filament\Contracts\Plugin;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Assets\Css;
use Filament\Support\Colors\Color;
use Filament\Support\Concerns\EvaluatesClosures;
use Filament\Support\Facades\FilamentColor;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use InvalidArgumentException;

class ThemesPlugin implements Plugin
{
    use EvaluatesClosures;

    public const string ID = 'asignua-filament-themes';

    /** Neutral palettes are not offered as a primary colour. */
    private const array NEUTRALS = ['gray', 'slate', 'zinc', 'neutral', 'stone', 'mauve', 'olive', 'mist', 'taupe'];

    /** @var array<string, class-string<Theme>>|null */
    protected ?array $themes = null;

    protected ?string $default = null;

    protected ?string $defaultColor = null;

    protected ?string $mode = null;

    /** null = the default: everybody in `user` mode, nobody in `global` mode (one choice restyles the panel for all). */
    protected bool|Closure|null $canChangeTheme = null;

    protected bool $servingRegistered = false;

    protected bool $userMenu = true;

    protected bool|Closure $navigation = false;

    protected ?string $navigationGroup = null;

    protected ?int $navigationSort = null;

    protected ?string $icon = null;

    /** @var list<string>|null */
    protected ?array $colors = null;

    protected bool $customColor = true;

    protected ?Panel $panel = null;

    protected bool $darkModeWrapped = false;

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(static::ID);

        return $plugin;
    }

    public function getId(): string
    {
        return static::ID;
    }

    // ---------------------------------------------------------------- configuration

    /**
     * The themes this panel offers, replacing the six built-in ones.
     *
     * @param array<int|string, mixed> $themes
     */
    public function themes(array $themes): static
    {
        $this->themes = [];

        return $this->registerTheme($themes);
    }

    /**
     * Adds themes to the list (or, with `$override`, replaces the list). Same signature as hasnayeen/themes.
     *
     * @param array<int|string, mixed> $themes
     */
    public function registerTheme(array $themes, bool $override = false): static
    {
        if ($themes === []) {
            throw new InvalidArgumentException('No themes provided.');
        }

        $registry = $override ? [] : $this->getThemes();

        foreach ($themes as $class) {
            if (!is_string($class) || !class_exists($class) || !is_subclass_of($class, Theme::class)) {
                throw new InvalidArgumentException('A theme must be a class implementing '.Theme::class.', got ['.(is_string($class) ? $class : get_debug_type($class)).'].');
            }

            $registry[$class::getName()] = $class;
        }

        $this->themes = $registry;

        return $this;
    }

    /**
     * The theme used until the user picks one: a class or a theme name.
     */
    public function default(string $theme): static
    {
        $this->default = is_subclass_of($theme, Theme::class) ? $theme::getName() : $theme;

        return $this;
    }

    /**
     * The primary colour used until the user picks one: a Filament colour name or `#rrggbb`.
     */
    public function defaultColor(?string $color): static
    {
        $this->defaultColor = $color;

        return $this;
    }

    /**
     * `user` (each user chooses) or `global` (one choice for the whole panel).
     */
    public function mode(string $mode): static
    {
        if (!in_array($mode, ['user', 'global'], true)) {
            throw new InvalidArgumentException("Mode must be [user] or [global], got [{$mode}].");
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * Who may open the page and change the theme. The closure may take `$user`.
     */
    public function canChangeTheme(bool|Closure|null $condition): static
    {
        $this->canChangeTheme = $condition;

        return $this;
    }

    /**
     * Alias of `canChangeTheme()` (hasnayeen/themes name).
     */
    public function canViewThemesPage(Closure $callback): static
    {
        return $this->canChangeTheme($callback);
    }

    public function userMenu(bool $condition = true): static
    {
        $this->userMenu = $condition;

        return $this;
    }

    /**
     * Also show the page in the sidebar.
     */
    public function navigation(bool|Closure $condition = true, ?string $group = null, ?int $sort = null): static
    {
        $this->navigation = $condition;
        $this->navigationGroup = $group;
        $this->navigationSort = $sort;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * The Filament colour names offered as a primary colour (default: every chromatic palette).
     *
     * @param array<int|string, string> $colors
     */
    public function colors(array $colors): static
    {
        $this->colors = array_values($colors);

        return $this;
    }

    /**
     * Allow a free `#rrggbb` primary colour next to the palette swatches.
     */
    public function customColor(bool $condition = true): static
    {
        $this->customColor = $condition;

        return $this;
    }

    // ---------------------------------------------------------------- reading

    /**
     * @return array<string, class-string<Theme>>
     */
    public function getThemes(): array
    {
        return $this->themes ?? self::builtIn();
    }

    /**
     * @return array<string, class-string<Theme>>
     */
    public static function builtIn(): array
    {
        $themes = [];

        foreach ([DefaultTheme::class, Nord::class, Dracula::class, Sunset::class, Ocean::class, Forest::class] as $class) {
            $themes[$class::getName()] = $class;
        }

        return $themes;
    }

    public function getDefaultTheme(): ?string
    {
        return $this->default;
    }

    public function getDefaultColor(): ?string
    {
        return $this->defaultColor;
    }

    public function getMode(): string
    {
        return $this->mode ?? (config('filament-themes.mode') === 'global' ? 'global' : 'user');
    }

    public function getIcon(): string
    {
        return $this->icon ?? (string) config('filament-themes.icon', 'heroicon-o-swatch');
    }

    /**
     * @return list<string>
     */
    public function getColors(): array
    {
        return $this->colors ?? array_values(array_diff(array_keys(Color::all()), self::NEUTRALS));
    }

    public function allowsCustomColor(): bool
    {
        return $this->customColor;
    }

    public function hasNavigation(): bool
    {
        return (bool) $this->evaluate($this->navigation);
    }

    public function getNavigationGroup(): ?string
    {
        return $this->navigationGroup;
    }

    public function getNavigationSort(): ?int
    {
        return $this->navigationSort;
    }

    public function canChange(?Authenticatable $user = null): bool
    {
        $user ??= $this->manager()->user();

        if ($user === null) {
            return false;
        }

        if ($this->canChangeTheme === null) {
            return $this->getMode() !== 'global';
        }

        return (bool) $this->evaluate($this->canChangeTheme, ['user' => $user], [Authenticatable::class => $user]);
    }

    /**
     * Hasnayeen/themes name for `get()->canChange()`.
     */
    public static function canView(): bool
    {
        return static::get()->canChange();
    }

    public function manager(): ThemeManager
    {
        return new ThemeManager($this, $this->panel ?? Filament::getCurrentPanel() ?? Filament::getDefaultPanel(), app(ThemeStore::class));
    }

    // ---------------------------------------------------------------- panel integration

    public function register(Panel $panel): void
    {
        $this->panel = $panel;

        $panel->pages([ThemesPage::class]);

        if ($this->userMenu) {
            $panel->userMenuItems([
                'themes' => Action::make('themes')
                    ->label(fn (): string => __('filament-themes::filament-themes.menu'))
                    ->icon(fn (): string => $this->getIcon())
                    ->url(fn (): string => ThemesPage::getUrl(panel: $panel->getId()))
                    ->visible(fn (): bool => $this->canChange()),
            ]);
        }

        $this->assertDefaultColorIsOffered();

        // Stylesheets of custom themes are published by `filament:assets` like any plugin asset.
        $assets = [];

        foreach ($this->getThemes() as $name => $class) {
            $path = static::resolveThemePath($class);

            if ($path !== null) {
                $assets[] = Css::make('filament-theme-'.$name, $path);
            }
        }

        if ($assets !== []) {
            $panel->assets($assets);
        }
    }

    /**
     * The absolute path of a theme's stylesheet, or null (with a log warning when a path is declared but is not a
     * file). A relative path - hasnayeen/themes themes return Vite entries such as `resources/css/themes/x.css` -
     * is resolved against `base_path()`.
     *
     * @param class-string<Theme> $class
     */
    public static function resolveThemePath(string $class): ?string
    {
        $path = $class::getPath();

        if ($path === null || $path === '') {
            return null;
        }

        if (!str_starts_with($path, '/') && !str_starts_with($path, '\\') && preg_match('~^[A-Za-z]:[\\\\/]~', $path) !== 1) {
            $path = base_path($path);
        }

        if (!is_file($path)) {
            Log::warning("filament-themes: the stylesheet [{$path}] of theme [{$class}] is not a file and was skipped. "
                .'A theme CSS must be a plain, compiled .css file (a Vite/Tailwind source from hasnayeen/themes is not enough).');

            return null;
        }

        return $path;
    }

    protected function assertDefaultColorIsOffered(): void
    {
        $color = $this->defaultColor;

        if ($color === null) {
            return;
        }

        $offered = Palette::isHex($color) ? $this->customColor : in_array($color, $this->getColors(), true);

        if (!$offered) {
            throw new InvalidArgumentException("The default colour [{$color}] is not offered: use one of the colors() names"
                .($this->customColor ? ' or a #rrggbb' : ' (custom colours are switched off)').'.');
        }
    }

    public function boot(Panel $panel): void
    {
        $this->panel = $panel;

        // Lazy on purpose: the closure runs when the page renders its colour variables, long after the session
        // started, so the right user's choice is read even though this plugin boots before the panel's stack.
        FilamentColor::register(fn (): array => $this->manager()->colors());

        $this->wrapDarkMode($panel);

        // The event dispatcher outlives the request under Octane: listen once, not on every boot.
        if (!$this->servingRegistered) {
            $this->servingRegistered = true;

            Filament::serving(fn () => Filament::getCurrentPanel()?->getId() === $panel->getId() ? $this->manager()->applyPanelConfig() : null);
        }

        FilamentView::registerRenderHook(
            PanelsRenderHook::HEAD_END,
            fn (): HtmlString => new HtmlString(
                Filament::getCurrentPanel()?->getId() === $panel->getId()
                    ? '<meta name="filament-theme" content="'.e($this->manager()->themeName()).'">'
                    : '',
            ),
        );
    }

    protected function wrapDarkMode(Panel $panel): void
    {
        if ($this->darkModeWrapped) {
            return;
        }

        $this->darkModeWrapped = true;

        // Keep the panel's own settings as they are (bool or closure) and evaluate them on every call.
        $read = fn (string $property): mixed => (fn (): mixed => $this->{$property})->call($panel);
        $evaluate = fn (mixed $value): bool => (bool) (fn (): mixed => $this->evaluate($value))->call($panel);
        $allowed = $read('hasDarkMode');
        $forced = $read('hasDarkModeForced');

        $panel->darkMode(
            fn (): bool => $this->manager()->isDarkOnly() || ($evaluate($allowed) && !$this->manager()->isLightOnly()),
            fn (): bool => $evaluate($forced) || $this->manager()->isDarkOnly(),
        );
    }
}

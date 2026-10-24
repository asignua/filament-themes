<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Pages;

use Asignua\FilamentThemes\Support\Palette;
use Asignua\FilamentThemes\ThemeManager;
use Asignua\FilamentThemes\ThemesPlugin;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Validation\ValidationException;
use UnitEnum;

class Themes extends Page
{
    protected static ?string $slug = 'themes';

    protected string $view = 'filament-themes::pages.themes';

    /** The `#rrggbb` typed into the custom colour field. */
    public string $customColor = '';

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $this->customColor = $this->manager()->colorSetting() !== null && Palette::isHex((string) $this->manager()->colorSetting())
            ? (string) $this->manager()->colorSetting()
            : '#3366ff';
    }

    public static function canAccess(): bool
    {
        return ThemesPlugin::get()->canChange();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return ThemesPlugin::get()->hasNavigation() && static::canAccess();
    }

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return ThemesPlugin::get()->getIcon();
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return ThemesPlugin::get()->getNavigationGroup();
    }

    public static function getNavigationSort(): ?int
    {
        return ThemesPlugin::get()->getNavigationSort();
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-themes::filament-themes.menu');
    }

    public function getTitle(): string|Htmlable
    {
        return __('filament-themes::filament-themes.title');
    }

    public function manager(): ThemeManager
    {
        return ThemesPlugin::get()->manager();
    }

    public function selectTheme(string $name): void
    {
        $this->authorizeChange();
        $this->manager()->selectTheme($name);

        $this->done(__('filament-themes::filament-themes.theme_saved', ['theme' => $this->manager()->card($name)['label']]));
    }

    public function selectColor(string $color): void
    {
        $this->authorizeChange();

        try {
            $this->manager()->selectColor($color);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages(['customColor' => $exception->errors()['color'] ?? []]);
        }

        $this->done(__('filament-themes::filament-themes.color_saved', ['color' => $color]));
    }

    public function applyCustomColor(): void
    {
        $this->authorizeChange();
        $this->customColor = strtolower(trim($this->customColor));

        $this->validate(['customColor' => ['required', 'regex:/^#[0-9a-f]{6}$/']], ['customColor.regex' => __('filament-themes::filament-themes.invalid_color')]);

        $this->selectColor($this->customColor);
    }

    public function resetColor(): void
    {
        $this->authorizeChange();
        $this->manager()->selectColor(null);

        $this->done(__('filament-themes::filament-themes.color_reset'));
    }

    protected function authorizeChange(): void
    {
        abort_unless(static::canAccess(), 403);
    }

    /**
     * The colours live in the document head, so a full reload (not a morph) shows the result.
     */
    protected function done(string $message): void
    {
        Notification::make()->title($message)->success()->send();

        $this->redirect(static::getUrl());
    }
}

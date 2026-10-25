<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Tests\Feature;

use Asignua\FilamentThemes\Contracts\CanModifyPanelConfig;
use Asignua\FilamentThemes\Contracts\HasOnlyDarkMode;
use Asignua\FilamentThemes\Contracts\HasOnlyLightMode;
use Asignua\FilamentThemes\Contracts\Theme;
use Asignua\FilamentThemes\Http\Middleware\SetTheme;
use Asignua\FilamentThemes\Models\ThemeSetting;
use Asignua\FilamentThemes\Support\Palette;
use Asignua\FilamentThemes\Support\ThemeChoice;
use Asignua\FilamentThemes\Tests\TestCase;
use Asignua\FilamentThemes\Themes\Dracula;
use Asignua\FilamentThemes\Themes\Nord;
use Asignua\FilamentThemes\ThemesPlugin;
use Asignua\FilamentThemes\ThemeStore;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Workbench\App\Models\User;

class ThemeApplicationTest extends TestCase
{
    /** primary-600 of the Dracula ladder (hue 295) and of the Nord ladder (hue 250). */
    private const string DRACULA_600 = 'oklch(0.48 0.16 295)';

    private const string NORD_600 = 'oklch(0.48 0.1 250)';

    private function choose(User $user, string $theme, ?string $color = null): void
    {
        app(ThemeStore::class)->put('admin', false, $user, new ThemeChoice($theme, $color));
        // The resolved choice is memoised on the request; a stored change needs a new one, as in production.
        $this->refreshRequest();
    }

    public function test_the_panel_default_applies_when_nothing_is_chosen(): void
    {
        $this->get('/admin')->assertOk()
            ->assertSee('<meta name="filament-theme" content="nord">', false)
            ->assertSee(self::NORD_600, false)
            ->assertDontSee(self::DRACULA_600, false);
    }

    public function test_the_colours_of_one_user_never_reach_another(): void
    {
        $first = auth()->user();
        $second = User::factory()->create();
        $this->choose($first, 'dracula');

        $this->getAs($first, '/admin')->assertOk()
            ->assertSee('content="dracula"', false)
            ->assertSee(self::DRACULA_600, false)
            ->assertDontSee(self::NORD_600, false);

        $this->getAs($second, '/admin')->assertOk()
            ->assertSee('content="nord"', false)
            ->assertSee(self::NORD_600, false)
            ->assertDontSee(self::DRACULA_600, false);
    }

    public function test_the_login_page_uses_the_panel_default(): void
    {
        $this->choose(auth()->user(), 'dracula');
        auth()->logout();
        $this->flushSession();

        $this->get('/admin/login')->assertOk()
            ->assertSee('content="nord"', false)
            ->assertDontSee(self::DRACULA_600, false);
    }

    public function test_a_chosen_primary_colour_replaces_the_theme_primary(): void
    {
        $this->choose(auth()->user(), 'dracula', '#aa3366');
        $expected = Palette::fromHex('#aa3366')[600];

        $this->get('/admin')->assertOk()
            ->assertSee($expected, false)
            ->assertDontSee(self::DRACULA_600, false);
    }

    public function test_a_named_filament_colour_is_used_as_is(): void
    {
        $this->choose(auth()->user(), 'nord', 'emerald');

        $this->get('/admin')->assertOk()->assertSee(\Filament\Support\Colors\Color::all()['emerald'][600], false);
    }

    public function test_global_mode_applies_one_choice_to_everybody(): void
    {
        ThemesPlugin::get()->mode('global');
        $other = User::factory()->create();

        app(ThemeStore::class)->put('admin', true, null, new ThemeChoice('sunset'));
        // A personal choice is ignored in global mode.
        $this->choose($other, 'forest');

        foreach ([auth()->user(), $other] as $user) {
            $this->getAs($user, '/admin')->assertOk()->assertSee('content="sunset"', false);
        }

        $this->assertSame('sunset', ThemeSetting::query()->where('subject_type', '')->sole()->theme);
    }

    public function test_global_mode_writes_the_panel_wide_row(): void
    {
        ThemesPlugin::get()->mode('global')->canChangeTheme(true);

        \Livewire\Livewire::test(\Asignua\FilamentThemes\Pages\Themes::class)->call('selectTheme', 'ocean');

        $row = ThemeSetting::query()->sole();
        $this->assertSame(['', '', 'ocean'], [$row->subject_type, $row->subject_id, $row->theme]);
    }

    public function test_the_mode_can_come_from_the_config_and_is_validated(): void
    {
        config(['filament-themes.mode' => 'global']);
        $this->assertSame('global', ThemesPlugin::make()->getMode());

        $this->expectException(InvalidArgumentException::class);
        ThemesPlugin::make()->mode('team');
    }

    public function test_a_custom_theme_can_be_registered_and_applied(): void
    {
        ThemesPlugin::get()->registerTheme([FixedTheme::class]);

        $this->assertArrayHasKey('fixed', ThemesPlugin::get()->getThemes());
        $this->assertArrayHasKey('nord', ThemesPlugin::get()->getThemes());

        $this->choose(auth()->user(), 'fixed');

        $this->get('/admin')->assertOk()->assertSee('content="fixed"', false);
        // A hex string is expanded into a full ladder.
        $this->assertSame(Palette::fromHex('#cc0066'), ThemesPlugin::get()->manager()->colors()['primary']);
    }

    public function test_register_theme_can_replace_the_list(): void
    {
        ThemesPlugin::get()->registerTheme([FixedTheme::class], override: true);

        $this->assertSame(['fixed'], array_keys(ThemesPlugin::get()->getThemes()));
    }

    public function test_a_class_that_is_not_a_theme_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ThemesPlugin::get()->registerTheme([\stdClass::class]);
    }

    public function test_an_empty_list_is_refused(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ThemesPlugin::get()->themes([]);
    }

    public function test_a_stored_theme_that_is_no_longer_offered_falls_back_to_the_default(): void
    {
        $this->choose(auth()->user(), 'forest');
        ThemesPlugin::get()->themes([Nord::class, Dracula::class])->default(Dracula::class);

        $this->assertSame('dracula', ThemesPlugin::get()->manager()->themeName());
    }

    public function test_the_default_colour_applies_until_a_user_picks_one(): void
    {
        ThemesPlugin::get()->defaultColor('rose');

        $this->assertSame('rose', ThemesPlugin::get()->manager()->colorSetting());

        $this->choose(auth()->user(), 'nord', '#112233');

        $this->assertSame('#112233', ThemesPlugin::get()->manager()->colorSetting());
    }

    public function test_dark_and_light_only_themes_drive_the_panel_dark_mode(): void
    {
        $panel = Filament::getPanel('admin');

        ThemesPlugin::get()->themes([Nord::class, DarkOnlyTheme::class, LightOnlyTheme::class]);
        Filament::bootCurrentPanel();

        $this->assertTrue($panel->hasDarkMode());
        $this->assertFalse($panel->hasDarkModeForced());

        $this->choose(auth()->user(), 'dark-only');
        $this->refreshRequest();
        $this->assertTrue($panel->hasDarkMode());
        $this->assertTrue($panel->hasDarkModeForced());

        $this->choose(auth()->user(), 'light-only');
        $this->refreshRequest();
        $this->assertFalse($panel->hasDarkMode());
        $this->assertFalse($panel->hasDarkModeForced());
    }

    public function test_the_panel_config_hook_runs_once_per_request_through_the_middleware(): void
    {
        ThemesPlugin::get()->themes([HookTheme::class])->default(HookTheme::class);
        HookTheme::$calls = 0;

        $middleware = new SetTheme;
        $request = Request::create('/admin');
        $this->app->instance('request', $request);

        $middleware->handle($request, fn () => response('ok'));
        $middleware->handle($request, fn () => response('ok'));

        $this->assertSame(1, HookTheme::$calls);
    }

    public function test_the_user_columns_storage_reads_and_writes_the_users_table(): void
    {
        config(['filament-themes.storage' => 'users']);
        Schema::table('users', function ($table): void {
            $table->string('theme')->nullable();
            $table->string('theme_color')->nullable();
        });

        $user = auth()->user()->fresh();
        $user->forceFill(['theme' => 'ocean', 'theme_color' => 'teal'])->save();
        $this->actingAs($user);
        $this->refreshRequest();
        $this->assertSame('ocean', ThemesPlugin::get()->manager()->themeName());
        $this->assertSame('teal', ThemesPlugin::get()->manager()->colorSetting());

        \Livewire\Livewire::test(\Asignua\FilamentThemes\Pages\Themes::class)->call('selectTheme', 'forest');

        $this->assertSame('forest', $user->fresh()->theme);
        $this->assertSame('teal', $user->fresh()->theme_color);
        $this->assertSame(0, ThemeSetting::query()->count());
    }

    public function test_stored_rows_are_unique_per_user_and_updated_in_place(): void
    {
        $user = auth()->user();
        $this->choose($user, 'nord');
        $this->choose($user, 'sunset', 'rose');
        $this->choose(User::factory()->create(), 'forest');

        $this->assertSame(2, ThemeSetting::query()->count());
        $this->assertSame('rose', ThemeSetting::query()->where('theme', 'sunset')->sole()->color);

        app(ThemeStore::class)->put('admin', false, $user, new ThemeChoice);
        $this->assertSame(1, ThemeSetting::query()->count());
    }

    /**
     * A request as a new PHP process sees it: scoped singletons (Filament's colour registry) start empty.
     */
    private function getAs(User $user, string $uri): \Illuminate\Testing\TestResponse
    {
        $this->flushSession();
        $this->app->forgetScopedInstances();
        \Filament\Support\Facades\FilamentColor::clearResolvedInstance(\Filament\Support\Colors\ColorManager::class);

        return $this->actingAs($user)->get($uri);
    }

    private function refreshRequest(): void
    {
        $this->app->instance('request', Request::create('/admin'));
    }
}

class DarkOnlyTheme extends FixedTheme implements HasOnlyDarkMode
{
    public static function getName(): string
    {
        return 'dark-only';
    }
}

class LightOnlyTheme extends FixedTheme implements HasOnlyLightMode
{
    public static function getName(): string
    {
        return 'light-only';
    }
}

class HookTheme extends FixedTheme implements CanModifyPanelConfig, Theme
{
    public static int $calls = 0;

    public static function getName(): string
    {
        return 'hook';
    }

    public function modifyPanelConfig(Panel $panel): Panel
    {
        self::$calls++;

        return $panel;
    }
}

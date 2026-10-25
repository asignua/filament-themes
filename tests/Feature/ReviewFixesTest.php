<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Tests\Feature;

use Asignua\FilamentThemes\Contracts\CanModifyPanelConfig;
use Asignua\FilamentThemes\Contracts\Theme;
use Asignua\FilamentThemes\Models\ThemeSetting;
use Asignua\FilamentThemes\Pages\Themes;
use Asignua\FilamentThemes\Support\Palette;
use Asignua\FilamentThemes\Support\ThemeChoice;
use Asignua\FilamentThemes\Tests\TestCase;
use Asignua\FilamentThemes\Themes\Nord;
use Asignua\FilamentThemes\ThemesPlugin;
use Asignua\FilamentThemes\ThemeStore;
use Filament\Events\ServingFilament;
use Filament\Facades\Filament;
use Filament\Panel;
use Filament\Support\Colors\Color;
use Filament\Support\Colors\ColorManager;
use Filament\Support\Facades\FilamentColor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Workbench\App\Models\User;

class ReviewFixesTest extends TestCase
{
    private function fresh(User $user, string $uri): \Illuminate\Testing\TestResponse
    {
        $this->flushSession();
        $this->app->forgetScopedInstances();
        FilamentColor::clearResolvedInstance(ColorManager::class);

        return $this->actingAs($user)->get($uri);
    }

    private function choose(User $user, string $theme): void
    {
        app(ThemeStore::class)->put('admin', false, $user, new ThemeChoice($theme));
        $this->app->instance('request', Request::create('/admin'));
    }

    // 1 ---------------------------------------------------------------------------------------------------------

    public function test_a_panel_hook_does_not_leak_into_the_next_request(): void
    {
        ThemesPlugin::get()->themes([Nord::class, LeakyTheme::class]);
        $a = auth()->user();
        $b = User::factory()->create();
        $this->choose($a, 'leaky');
        $panel = Filament::getPanel('admin');

        $this->fresh($a, '/admin')->assertOk()->assertSee('Leaked Brand');
        $this->app->terminate();

        $this->assertNotSame('Leaked Brand', $panel->getBrandName());
        $this->assertFalse($panel->hasTopNavigation());
        $this->assertTrue($panel->hasDarkMode());

        $this->fresh($b, '/admin')->assertOk()->assertDontSee('Leaked Brand');
        $this->app->terminate();
        $this->assertFalse($panel->hasTopNavigation());
    }

    // 2 ---------------------------------------------------------------------------------------------------------

    public function test_global_mode_denies_by_default_and_allows_when_explicit(): void
    {
        $plugin = ThemesPlugin::get()->mode('global');

        $this->assertFalse(Themes::canAccess());
        $this->get('/admin/themes')->assertForbidden();

        $plugin->canChangeTheme(true);
        $this->assertTrue(Themes::canAccess());

        $plugin->canChangeTheme(fn (User $user): bool => false);
        $this->assertFalse(Themes::canAccess());
    }

    public function test_user_mode_still_allows_by_default(): void
    {
        $this->assertTrue(Themes::canAccess());
    }

    // 3 ---------------------------------------------------------------------------------------------------------

    public function test_a_relative_theme_css_path_is_resolved_against_base_path(): void
    {
        $dir = base_path('css-test');
        @mkdir($dir);
        file_put_contents($dir.'/x.css', 'a{}');
        RelativePathTheme::$path = 'css-test/x.css';

        $this->assertSame(base_path('css-test/x.css'), ThemesPlugin::resolveThemePath(RelativePathTheme::class));

        RelativePathTheme::$path = base_path('css-test/x.css');
        $this->assertSame(base_path('css-test/x.css'), ThemesPlugin::resolveThemePath(RelativePathTheme::class));

        unlink($dir.'/x.css');
        rmdir($dir);
    }

    public function test_a_missing_theme_css_file_logs_a_warning_and_is_skipped(): void
    {
        Log::spy();
        RelativePathTheme::$path = 'resources/js/themes/missing.css';

        $this->assertNull(ThemesPlugin::resolveThemePath(RelativePathTheme::class));

        Log::shouldHaveReceived('warning')->once();
    }

    // 4 ---------------------------------------------------------------------------------------------------------

    public function test_the_stylesheet_is_layered_and_leaves_button_groups_alone(): void
    {
        $css = (string) file_get_contents(__DIR__.'/../../resources/dist/filament-themes.css');

        $this->assertStringStartsWith('@layer theme, base, components, utilities;', $css);
        $this->assertSame(1, substr_count($css, '@layer components {'));

        // Every rule after the layer statement lives inside the single components block.
        $body = substr($css, strlen('@layer theme, base, components, utilities;'));
        $depthZeroRules = preg_replace('/@layer components \{.*\}\s*\z/s', '', $body);
        $this->assertSame('', trim((string) preg_replace('#/\*.*?\*/#s', '', (string) $depthZeroRules)));

        // Button radius never reaches a button inside a group (its corners are Filament's).
        $this->assertStringContainsString(':not(.fi-btn-group > .fi-btn)', $css);
        $this->assertDoesNotMatchRegularExpression('/\.fi-btn,\s/', $css);
    }

    // 5 ---------------------------------------------------------------------------------------------------------

    public function test_booting_the_panel_again_does_not_add_listeners(): void
    {
        $plugin = ThemesPlugin::get();
        $panel = Filament::getPanel('admin');
        $plugin->boot($panel);
        $before = count(Event::getListeners(ServingFilament::class));

        $plugin->boot($panel);
        $plugin->boot($panel);

        $this->assertSame($before, count(Event::getListeners(ServingFilament::class)));
    }

    // 6 ---------------------------------------------------------------------------------------------------------

    public function test_hex_detection_rejects_a_trailing_newline_and_input_is_trimmed(): void
    {
        $this->assertFalse(Palette::isHex("#aa3366\n"));

        ThemesPlugin::get()->manager()->selectColor(' #AA3366 ');

        $this->assertSame('#aa3366', ThemeSetting::query()->sole()->color);
    }

    // 7 ---------------------------------------------------------------------------------------------------------

    public function test_storing_twice_is_one_upsert_that_keeps_the_creation_time(): void
    {
        $user = auth()->user();
        $store = app(ThemeStore::class);
        $store->put('admin', false, $user, new ThemeChoice('nord'));
        $created = ThemeSetting::query()->sole()->created_at;

        $this->travel(5)->minutes();
        $store->put('admin', false, $user, new ThemeChoice('forest', 'rose'));

        $row = ThemeSetting::query()->sole();
        $this->assertSame(['forest', 'rose'], [$row->theme, $row->color]);
        $this->assertTrue($created->equalTo($row->created_at));
        $this->assertTrue($row->updated_at->greaterThan($created));
    }

    // 8 ---------------------------------------------------------------------------------------------------------

    public function test_the_panel_dark_mode_setting_is_read_per_request_not_frozen_at_boot(): void
    {
        $panel = Filament::getPanel('admin');
        config(['themes-test.dark' => true]);
        $panel->darkMode(fn (): bool => (bool) config('themes-test.dark'));
        $plugin = ThemesPlugin::get();
        (fn () => $this->darkModeWrapped = false)->call($plugin);

        Filament::bootCurrentPanel();
        $this->assertTrue($panel->hasDarkMode());

        config(['themes-test.dark' => false]);
        $this->assertFalse($panel->hasDarkMode());
    }

    // 9 ---------------------------------------------------------------------------------------------------------

    public function test_the_default_card_uses_the_colours_of_the_panel(): void
    {
        Filament::getPanel('admin')->colors(['primary' => Color::Rose]);
        ThemesPlugin::get()->themes([\Asignua\FilamentThemes\Themes\DefaultTheme::class, Nord::class])->default('default');

        $card = ThemesPlugin::get()->manager()->card('default');

        $this->assertSame(Color::Rose[600], $card['shades']['primary']);
    }

    // 10 --------------------------------------------------------------------------------------------------------

    public function test_a_gray_given_as_hex_gets_a_neutral_ladder(): void
    {
        ThemesPlugin::get()->themes([GrayHexTheme::class])->default(GrayHexTheme::class);

        $gray = ThemesPlugin::get()->manager()->colors()['gray'];

        $this->assertEqualsWithDelta(0.54, (float) sscanf($gray[500], 'oklch(%f')[0], 0.001);
        $this->assertLessThanOrEqual(0.03, (float) sscanf($gray[500], 'oklch(%f %f')[1]);
    }

    public function test_a_default_colour_that_cannot_be_chosen_is_refused(): void
    {
        foreach ([
            fn (ThemesPlugin $p) => $p->colors(['blue'])->defaultColor('rose'),
            fn (ThemesPlugin $p) => $p->customColor(false)->defaultColor('#aa3366'),
            fn (ThemesPlugin $p) => $p->defaultColor('not-a-colour'),
        ] as $configure) {
            $plugin = $configure(new ThemesPlugin);

            try {
                $plugin->register(Filament::getPanel('admin'));
                $this->fail('A default colour that is not offered must be refused.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function test_a_valid_default_colour_is_accepted(): void
    {
        $plugin = (new ThemesPlugin)->defaultColor('rose');
        $plugin->register(Filament::getPanel('admin'));

        $this->assertSame('rose', $plugin->getDefaultColor());
    }
}

class LeakyTheme extends FixedTheme implements CanModifyPanelConfig, Theme
{
    public static function getName(): string
    {
        return 'leaky';
    }

    public function modifyPanelConfig(Panel $panel): Panel
    {
        return $panel->topNavigation()->brandName('Leaked Brand')->darkMode(false);
    }
}

class RelativePathTheme extends FixedTheme
{
    public static string $path = '';

    public static function getName(): string
    {
        return 'relative';
    }

    public static function getPath(): ?string
    {
        return self::$path;
    }
}

class GrayHexTheme extends FixedTheme
{
    public static function getName(): string
    {
        return 'grayhex';
    }

    public function getThemeColor(): array
    {
        return ['primary' => '#cc0066', 'gray' => '#334155'];
    }
}

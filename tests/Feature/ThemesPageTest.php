<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Tests\Feature;

use Asignua\FilamentThemes\Models\ThemeSetting;
use Asignua\FilamentThemes\Pages\Themes;
use Asignua\FilamentThemes\Tests\TestCase;
use Asignua\FilamentThemes\ThemesPlugin;
use Filament\Notifications\Notification;
use Livewire\Livewire;
use Workbench\App\Models\User;

class ThemesPageTest extends TestCase
{
    public function test_the_page_renders_a_card_per_theme_and_marks_the_default(): void
    {
        $page = Livewire::test(Themes::class)->assertSuccessful();

        foreach (['default', 'nord', 'dracula', 'sunset', 'ocean', 'forest'] as $name) {
            $page->assertSeeHtml('data-theme-card="'.$name.'"');
        }

        // The workbench panel defaults to Nord.
        $page->assertSeeHtml('data-theme-card="nord"');
        $this->assertSame('nord', ThemesPlugin::get()->manager()->themeName());
    }

    public function test_the_page_is_reachable_over_http_and_offered_in_the_user_menu(): void
    {
        $this->get('/admin/themes')->assertOk()->assertSee(__('filament-themes::filament-themes.title'));
        $this->get('/admin')->assertOk()->assertSee(__('filament-themes::filament-themes.menu'));
    }

    public function test_a_guest_is_sent_to_the_login(): void
    {
        auth()->logout();
        $this->flushSession();

        $this->get('/admin/themes')->assertRedirect();
    }

    public function test_the_gate_closes_the_page_the_menu_item_and_the_actions(): void
    {
        ThemesPlugin::get()->canChangeTheme(false);

        $this->get('/admin/themes')->assertForbidden();
        $this->assertFalse(Themes::canAccess());
        $this->assertFalse(Themes::shouldRegisterNavigation());

        Livewire::test(Themes::class)->assertForbidden();
    }

    public function test_the_gate_receives_the_user(): void
    {
        $emma = User::factory()->create(['email' => 'emma@example.com']);

        ThemesPlugin::get()->canChangeTheme(fn (User $user): bool => $user->email === 'emma@example.com');

        $this->assertFalse(Themes::canAccess());

        $this->actingAs($emma);

        $this->assertTrue(Themes::canAccess());
    }

    public function test_selecting_a_theme_persists_it_for_that_user_and_notifies(): void
    {
        Livewire::test(Themes::class)->call('selectTheme', 'dracula')->assertRedirect(Themes::getUrl());

        Notification::assertNotified();

        $row = ThemeSetting::query()->sole();
        $this->assertSame('dracula', $row->theme);
        $this->assertSame(User::class, $row->subject_type);
        $this->assertSame((string) auth()->id(), $row->subject_id);
        $this->assertSame('admin', $row->panel_id);
    }

    public function test_an_unknown_or_disabled_theme_is_rejected_and_nothing_is_stored(): void
    {
        Livewire::test(Themes::class)->call('selectTheme', 'does-not-exist')->assertHasErrors('theme');

        ThemesPlugin::get()->themes([\Asignua\FilamentThemes\Themes\Nord::class]);

        Livewire::test(Themes::class)->call('selectTheme', 'dracula')->assertHasErrors('theme');

        $this->assertSame(0, ThemeSetting::query()->count());
    }

    public function test_a_palette_colour_and_a_custom_hex_are_stored_and_can_be_reset(): void
    {
        Livewire::test(Themes::class)->call('selectColor', 'emerald');
        $this->assertSame('emerald', ThemeSetting::query()->sole()->color);

        Livewire::test(Themes::class)->set('customColor', '#AA3366')->call('applyCustomColor')->assertHasNoErrors();
        $this->assertSame('#aa3366', ThemeSetting::query()->sole()->color);

        Livewire::test(Themes::class)->call('resetColor');
        $this->assertSame(0, ThemeSetting::query()->count());
    }

    public function test_an_invalid_colour_is_rejected(): void
    {
        Livewire::test(Themes::class)->set('customColor', 'blue')->call('applyCustomColor')->assertHasErrors('customColor');
        Livewire::test(Themes::class)->set('customColor', '#12345')->call('applyCustomColor')->assertHasErrors('customColor');
        Livewire::test(Themes::class)->call('selectColor', 'not-a-colour')->assertHasErrors('customColor');
        Livewire::test(Themes::class)->call('selectColor', 'gray')->assertHasErrors('customColor');

        $this->assertSame(0, ThemeSetting::query()->count());
    }

    public function test_a_custom_hex_can_be_switched_off(): void
    {
        ThemesPlugin::get()->customColor(false);

        Livewire::test(Themes::class)->call('selectColor', '#aa3366')->assertHasErrors('customColor');
        $this->assertSame(0, ThemeSetting::query()->count());
    }

    public function test_the_actions_are_blocked_without_permission(): void
    {
        $page = Livewire::test(Themes::class);

        ThemesPlugin::get()->canChangeTheme(false);

        $page->call('selectTheme', 'ocean')->assertForbidden();
        $this->assertSame(0, ThemeSetting::query()->count());
    }

    public function test_the_colour_picker_is_hidden_for_a_theme_without_a_changeable_colour(): void
    {
        ThemesPlugin::get()->themes([FixedTheme::class])->default(FixedTheme::class);

        Livewire::test(Themes::class)->assertDontSeeHtml('data-color="blue"');
    }
}

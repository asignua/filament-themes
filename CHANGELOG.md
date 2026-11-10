# Changelog

All notable changes to `asignua/filament-themes` are documented here.

## v1.1.0 - 2026-11-10

- Requires Filament ^5.11.2; replaced deprecated Filament API calls: Requires Filament ^5.11.2; no deprecated Filament API is used (verified with phpstan-deprecation-rules).

## v1.0.1 - 2026-11-07
- Fix: the panel crashed on boot on Filament 5.11.1 (`Undefined property: Filament\Panel::$hasDarkModeForced`), because Filament renamed the property to `$isDarkModeForced`. Both names are supported, tested on 5.9.0, 5.11.0 and 5.11.1.

## v1.0.0 - 2026-10-25
- Initial release: the Themes page (cards with light and dark previews, primary colour swatches and a free `#rrggbb`),
  six built-in themes (Default, Nord, Dracula, Sunset, Ocean, Forest) with WCAG AA contrast guaranteed by tests, per-user or
  panel-wide choice, per-panel theme lists, a `canChangeTheme` gate, custom themes as plain PHP classes
  (`hasnayeen/themes` contract), storage in a `theme_settings` table or in the user model columns, translations in 10 languages.

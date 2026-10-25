<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes;

use Asignua\FilamentThemes\Models\ThemeSetting;
use Asignua\FilamentThemes\Support\ThemeChoice;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;

/**
 * Reads and writes the stored choice. Two layouts: the `theme_settings` table (default; any authenticatable,
 * nothing added to your users table) and the `theme` / `theme_color` columns of the user model (the layout of
 * hasnayeen/themes). The global (panel-wide) choice always lives in the table.
 */
class ThemeStore
{
    public function get(string $panelId, bool $global, ?Authenticatable $user): ThemeChoice
    {
        if (!$global && $user === null) {
            return new ThemeChoice;
        }

        if (!$global && $this->usesUserColumns() && $user instanceof Model) {
            return new ThemeChoice($this->string($user->getAttribute('theme')), $this->string($user->getAttribute('theme_color')));
        }

        [$type, $id] = $this->subject($global, $user);

        $row = ThemeSetting::query()
            ->where('panel_id', $panelId)
            ->where('subject_type', $type)
            ->where('subject_id', $id)
            ->first();

        return new ThemeChoice($this->string($row?->theme), $this->string($row?->color));
    }

    public function put(string $panelId, bool $global, ?Authenticatable $user, ThemeChoice $choice): void
    {
        if (!$global && $user === null) {
            return;
        }

        if (!$global && $this->usesUserColumns() && $user instanceof Model) {
            $user->forceFill(['theme' => $choice->theme, 'theme_color' => $choice->color])->save();

            return;
        }

        [$type, $id] = $this->subject($global, $user);

        $query = ThemeSetting::query()
            ->where('panel_id', $panelId)
            ->where('subject_type', $type)
            ->where('subject_id', $id);

        if ($choice->isEmpty()) {
            $query->delete();

            return;
        }

        // One atomic statement: two simultaneous first saves cannot collide on the unique index.
        $now = now();

        ThemeSetting::query()->upsert(
            [['panel_id' => $panelId, 'subject_type' => $type, 'subject_id' => $id, 'theme' => $choice->theme, 'color' => $choice->color, 'created_at' => $now, 'updated_at' => $now]],
            ['panel_id', 'subject_type', 'subject_id'],
            ['theme', 'color', 'updated_at'],
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function subject(bool $global, ?Authenticatable $user): array
    {
        if ($global || $user === null) {
            return ['', ''];
        }

        $type = $user instanceof Model ? $user->getMorphClass() : $user::class;

        return [$type, (string) $user->getAuthIdentifier()];
    }

    private function usesUserColumns(): bool
    {
        return config('filament-themes.storage') === 'users';
    }

    private function string(mixed $value): ?string
    {
        return is_string($value) && $value !== '' ? $value : null;
    }
}

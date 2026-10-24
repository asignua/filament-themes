<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One chosen theme: per panel, and per authenticatable (`subject_*`) or for the whole panel (both subject
 * columns are an empty string, not NULL, so the unique index also works for the global row).
 *
 * @property int $id
 * @property string $panel_id
 * @property string $subject_type
 * @property string $subject_id
 * @property string|null $theme
 * @property string|null $color
 */
class ThemeSetting extends Model
{
    protected $guarded = [];

    public function getTable(): string
    {
        return (string) config('filament-themes.table', 'theme_settings');
    }
}

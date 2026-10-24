<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Tests\Feature;

use Asignua\FilamentThemes\Models\ThemeSetting;
use Asignua\FilamentThemes\Tests\TestCase;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Schema;

class MigrationTest extends TestCase
{
    public function test_the_unique_index_covers_panel_and_subject(): void
    {
        $index = collect(Schema::getIndexes('theme_settings'))->firstWhere('name', 'theme_settings_subject_unique');

        $this->assertSame(['panel_id', 'subject_type', 'subject_id'], $index['columns']);
    }

    /**
     * SQLite ignores key length; MySQL / MariaDB refuse an index over 3072 bytes (utf8mb4 = 4 bytes a character),
     * and 767 on very old row formats. SQLite does not report the declared lengths either, so they are read from the
     * migration source itself: every column of the unique index must declare a length, and the sum must fit.
     */
    public function test_the_unique_index_fits_the_mysql_key_length_limit(): void
    {
        $source = (string) file_get_contents(__DIR__.'/../../database/migrations/create_filament_themes_table.php.stub');
        preg_match("/unique\(\[([^\]]+)\]/", $source, $match);
        $columns = array_map(fn (string $c): string => trim($c, " '"), explode(',', $match[1]));

        $this->assertSame(['panel_id', 'subject_type', 'subject_id'], $columns);

        $bytes = 0;

        foreach ($columns as $column) {
            $this->assertSame(1, preg_match("/string\('{$column}', (\d+)\)/", $source, $m), "{$column} must declare a length.");
            $bytes += (int) $m[1] * 4;
        }

        $this->assertLessThanOrEqual(1000, $bytes);
    }

    public function test_the_same_subject_cannot_be_stored_twice_not_even_the_global_one(): void
    {
        ThemeSetting::query()->create(['panel_id' => 'admin', 'theme' => 'nord']);

        $this->expectException(UniqueConstraintViolationException::class);

        ThemeSetting::query()->create(['panel_id' => 'admin', 'theme' => 'dracula']);
    }

    public function test_the_migration_is_publishable(): void
    {
        $this->assertFileExists(__DIR__.'/../../database/migrations/create_filament_themes_table.php.stub');
    }
}

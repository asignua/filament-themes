<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('filament-themes.table', 'theme_settings'), function (Blueprint $table): void {
            $table->id();
            $table->string('panel_id', 64);
            // Morph class and key of the user; both are '' for the panel-wide (global) choice.
            // Unique index = (64 + 120 + 64) x 4 bytes = 992 < 1000: safe on every MySQL / MariaDB row format.
            $table->string('subject_type', 120)->default('');
            $table->string('subject_id', 64)->default('');
            $table->string('theme', 64)->nullable();
            $table->string('color', 32)->nullable();
            $table->timestamps();

            $table->unique(['panel_id', 'subject_type', 'subject_id'], 'theme_settings_subject_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('filament-themes.table', 'theme_settings'));
    }
};

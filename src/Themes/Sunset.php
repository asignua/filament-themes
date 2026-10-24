<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Themes;

class Sunset extends PaletteTheme
{
    public static function getName(): string
    {
        return 'sunset';
    }

    protected function hues(): array
    {
        return [
            'primary' => [45, 0.15],
            'info' => [250, 0.1],
            'success' => [150, 0.1],
            'warning' => [85, 0.12],
            'danger' => [20, 0.17],
            'gray' => [55, 0.012],
        ];
    }
}

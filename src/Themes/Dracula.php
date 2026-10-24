<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Themes;

class Dracula extends PaletteTheme
{
    public static function getName(): string
    {
        return 'dracula';
    }

    protected function hues(): array
    {
        return [
            'primary' => [295, 0.16],
            'info' => [215, 0.1],
            'success' => [150, 0.12],
            'warning' => [85, 0.12],
            'danger' => [15, 0.17],
            'gray' => [285, 0.028],
        ];
    }
}

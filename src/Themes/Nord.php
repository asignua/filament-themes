<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Themes;

class Nord extends PaletteTheme
{
    public static function getName(): string
    {
        return 'nord';
    }

    protected function hues(): array
    {
        return [
            'primary' => [250, 0.1],
            'info' => [235, 0.09],
            'success' => [135, 0.09],
            'warning' => [70, 0.1],
            'danger' => [20, 0.12],
            'gray' => [255, 0.02],
        ];
    }
}

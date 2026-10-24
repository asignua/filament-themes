<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Themes;

class Ocean extends PaletteTheme
{
    public static function getName(): string
    {
        return 'ocean';
    }

    protected function hues(): array
    {
        return [
            'primary' => [230, 0.11],
            'info' => [250, 0.12],
            'success' => [170, 0.1],
            'warning' => [80, 0.12],
            'danger' => [20, 0.16],
            'gray' => [235, 0.014],
        ];
    }
}

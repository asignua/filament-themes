<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Themes;

class Forest extends PaletteTheme
{
    public static function getName(): string
    {
        return 'forest';
    }

    protected function hues(): array
    {
        return [
            'primary' => [150, 0.11],
            'info' => [220, 0.09],
            'success' => [140, 0.13],
            'warning' => [80, 0.11],
            'danger' => [25, 0.15],
            'gray' => [140, 0.012],
        ];
    }
}

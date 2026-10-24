<?php

declare(strict_types=1);

return [
    /*
    | Who a choice belongs to: `user` (each user picks their own) or `global` (one choice for the whole panel).
    | Override per panel with ThemesPlugin::make()->mode('global').
    */
    'mode' => 'user',

    /*
    | Where a per-user choice is stored:
    |   `table` - the `theme_settings` table (publish the migration; works with any authenticatable)
    |   `users` - the `theme` and `theme_color` columns of the user model (the layout hasnayeen/themes used)
    | Global mode always uses the table.
    */
    'storage' => 'table',

    /*
    | Table of the `table` storage.
    */
    'table' => 'theme_settings',

    /*
    | The user menu icon and the defaults when nothing is chosen yet.
    */
    'icon' => 'heroicon-o-swatch',

    'default' => [
        'theme' => 'default',
        'theme_color' => null,
    ],
];

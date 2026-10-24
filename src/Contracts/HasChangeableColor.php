<?php

declare(strict_types=1);

namespace Asignua\FilamentThemes\Contracts;

/**
 * The theme allows a user to replace its primary colour.
 */
interface HasChangeableColor
{
    /**
     * The primary palette used while no override is chosen (an empty array keeps the panel's own primary).
     *
     * @return array<int|string, string>
     */
    public function getPrimaryColor(): array;
}

<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Products\Pages;

use Filament\Resources\Pages\CreateRecord;
use Workbench\App\Filament\Resources\Products\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;
}

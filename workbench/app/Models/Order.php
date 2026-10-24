<?php

declare(strict_types=1);

namespace Workbench\App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return ['total' => 'decimal:2', 'placed_at' => 'datetime'];
    }
}

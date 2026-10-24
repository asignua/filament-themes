<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Orders;

use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\Orders\Pages\CreateOrder;
use Workbench\App\Filament\Resources\Orders\Pages\EditOrder;
use Workbench\App\Filament\Resources\Orders\Pages\ListOrders;
use Workbench\App\Models\Order;

/**
 * Status badges in every semantic colour (success, warning, info, danger, gray).
 */
class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shopping-cart';

    /** @var array<string, string> */
    public static array $statuses = ['pending' => 'warning', 'paid' => 'success', 'shipped' => 'info', 'cancelled' => 'danger', 'refunded' => 'gray'];

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('number')->required(),
            TextInput::make('customer')->required(),
            Select::make('status')->options(array_combine(array_keys(static::$statuses), array_map('ucfirst', array_keys(static::$statuses))))->required(),
            TextInput::make('total')->numeric()->prefix('€')->required(),
            DateTimePicker::make('placed_at'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')->searchable()->weight('medium'),
                TextColumn::make('customer')->searchable(),
                TextColumn::make('status')->badge()->color(fn (string $state): string => static::$statuses[$state] ?? 'gray'),
                TextColumn::make('total')->money('EUR')->sortable(),
                TextColumn::make('placed_at')->dateTime()->sortable(),
            ])
            ->recordActions([EditAction::make()])
            ->defaultSort('placed_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit' => EditOrder::route('/{record}/edit'),
        ];
    }
}

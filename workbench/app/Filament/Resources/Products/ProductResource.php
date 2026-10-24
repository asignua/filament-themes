<?php

declare(strict_types=1);

namespace Workbench\App\Filament\Resources\Products;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Workbench\App\Filament\Resources\Products\Pages\CreateProduct;
use Workbench\App\Filament\Resources\Products\Pages\EditProduct;
use Workbench\App\Filament\Resources\Products\Pages\ListProducts;
use Workbench\App\Models\Product;

/**
 * Forms, toggles and a table, so every theme can be judged on real controls.
 */
class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->required(),
            TextInput::make('sku')->label('SKU')->required(),
            Select::make('category')->options(['Boxes' => 'Boxes', 'Pallets' => 'Pallets', 'Film' => 'Film', 'Labels' => 'Labels', 'Tape' => 'Tape'])->required(),
            TextInput::make('price')->numeric()->prefix('€')->required(),
            TextInput::make('stock')->numeric()->default(0),
            Toggle::make('is_active')->label('Active')->default(true),
            Textarea::make('description')->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->weight('medium'),
                TextColumn::make('sku')->label('SKU')->color('gray'),
                TextColumn::make('category')->badge(),
                TextColumn::make('price')->money('EUR')->sortable(),
                TextColumn::make('stock')->badge()->color(fn (int $state): string => $state === 0 ? 'danger' : ($state < 50 ? 'warning' : 'success')),
                IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->defaultSort('id');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}

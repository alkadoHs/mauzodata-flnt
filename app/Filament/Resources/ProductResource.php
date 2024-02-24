<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->columns(['sm' => 2, 'md' => 3])
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->columnSpan(['sm' => 2, 'md' => 3])
                    ->maxLength(255),
                Forms\Components\TextInput::make('buying_price')
                    ->translateLabel()
                    ->columnSpan(1)
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('selling_price')
                    ->translateLabel()
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->columnSpan(1)
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('stock')
                    ->translateLabel()
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->columnSpan(1)
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('unit')
                    ->translateLabel()
                    ->columnSpan(1)
                    ->maxLength(255),
                Forms\Components\TextInput::make('stock_alert')
                    ->translateLabel()
                    ->mask(RawJs::make('$money($input)'))
                    ->stripCharacters(',')
                    ->required()
                    ->columnSpan(1)
                    ->numeric()
                    ->default(0.00),
                Forms\Components\DatePicker::make('expire_date')
                    ->columnSpan(1),

                Section::make('Additional Information')
                    ->icon('heroicon-o-information-circle')
                    ->description(__('Fill this area if you sell both by retail and whole sale'))
                    ->columns(2)
                    ->columnSpan(['sm' => 2, 'md' => 3])
                    ->schema([
                        Forms\Components\TextInput::make('whole_sale')
                            ->helperText(__('Amount of stock you can sell by whole price'))
                            ->translateLabel()
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->numeric(),
                        Forms\Components\TextInput::make('discount')
                            ->prefix('Tsh')
                            ->mask(RawJs::make('$money($input)'))
                            ->stripCharacters(',')
                            ->translateLabel()
                            ->numeric(),
                        Forms\Components\TextInput::make('barcode')
                            ->label(__('Barcode(ISBN, UPC, GTIN, etc.)'))
                            ->helperText(__('This can be used to easly find your product'))
                            ->translateLabel()
                            ->columnSpan(['sm' => 1, 'md' => 2])
                            ->maxLength(255),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->translateLabel()
                    ->searchable(),
                Tables\Columns\TextColumn::make('barcode')
                    ->translateLabel()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('buying_price')
                    ->translateLabel()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('selling_price')
                    ->translateLabel()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->translateLabel()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('unit')
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('stock_alert')
                    ->translateLabel()
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('expire_date')
                    ->translateLabel()
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->translateLabel()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->translateLabel()
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}

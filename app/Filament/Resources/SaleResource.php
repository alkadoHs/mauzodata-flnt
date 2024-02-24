<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SaleResource\Pages;
use App\Filament\Resources\SaleResource\RelationManagers;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Sale;
use Filament\Facades\Filament;
use Filament\Forms;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SaleResource extends Resource
{
    protected static ?string $model = Sale::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Sale Information')
                    ->columns(2)
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label(__('Customer'))
                            ->options(
                                Customer::where('account_id', Filament::getTenant()->id)->get()->pluck('name', 'id')
                            )
                            ->searchable(),
                    ]),

                Repeater::make('saleItems')
                    ->relationship('saleItems')
                    ->columnSpanFull()
                    ->columns(6)
                    ->schema([
                        Select::make('product_id')
                            ->options(
                                Product::where('account_id', Filament::getTenant()->id)->get()->pluck('name', 'id')
                            )
                            ->label(__('Product'))
                            ->live()
                            ->afterStateUpdated(
                                fn (?int $state, Set $set) => $set('price', Product::find($state)->selling_price)
                                )
                            ->disableOptionWhen(
                                        fn ($value, $state, Get $get) => collect($get('../*.product_id'))
                                                                             ->reject(fn ($id) => $id == $state)
                                                                             ->filter()
                                                                             ->contains($value)
                            )
                            ->searchable()
                            ->columnSpan(2)
                            ->required(),
                        TextInput::make('quantity')
                            ->label(__('Quantity'))
                            ->columnSpan(1)
                            ->numeric()
                            ->live(debounce: '1s')
                            ->afterStateUpdated(
                                function ($state, Set $set, Get $get) {
                                    $product = Product::find($get('product_id'));
                                    return $state >= $product->whole_sale ? $set('discount', $product->discount): $set('discount', 0);
                                } 
                            )
                            ->required()
                            ->default(1)
                            ->required(),
                        TextInput::make('price')
                            ->label(__('Price'))
                            ->columnSpan(1)
                            ->numeric()
                            ->required(),
                        TextInput::make('discount')
                            ->label(__('Discount'))
                            ->columnSpan(1)
                            ->numeric()
                            ->live(debounce: '1s')
                            ->default(0),
                        Placeholder::make('total')
                            ->label(__('Total'))
                            ->columnSpan(1)
                            ->content(fn (Get $get): ?string => number_format((float) $get('quantity') * ($get('price') - $get('discount')))),

                    ])
                    ->live(debounce: '1s')
                     ->afterStateUpdated(
                                fn (Get $get, Set $set) => static::updateTotals($get, $set)
                            )
                    ->deleteAction(
                        fn (Get $get, Set $set) => static::updateTotals($get, $set)
                    ),

                    Section::make('Order Summary')
                        ->columns(3)
                        ->schema([
                            Forms\Components\Select::make('payment_method_id')
                                ->label(__('Payment Method'))
                                ->options(
                                    PaymentMethod::where('account_id', Filament::getTenant()->id)->get()->pluck('name', 'id')
                                )
                                ->searchable()
                                ->required(),
                            Forms\Components\TextInput::make('subtotal')
                                    ->afterStateHydrated(fn (Get $get, Set $set) => static::updateTotals($get, $set))
                                    ->disabled(),
                            Forms\Components\TextInput::make("paid")
                                    ->numeric()
                                    ->mask(RawJs::make('$money($input)'))
                                    ->stripCharacters(',')
                                    ->required(),
                        ]),
            ]);
    }


     public static function updateTotals(Get $get, Set $set): void
    {
        $selectedProducts = collect($get('saleItems'))->filter(fn($item) => !empty($item['product_id'] && !empty($item['quantity'])));

        foreach ($selectedProducts as $selectedProduct) {
            $product = Product::find($selectedProduct['product_id']);

            if($product->stock < $selectedProduct['quantity']) {
                Notification::make()
                    ->title('Stock is not enough!')
                    ->body("Your stock is $product->stock, update the quantity according to your stock balance")
                    ->danger()
                    ->color('danger')
                    ->send();
                return;
            }
        }

        $subtotal = $selectedProducts->reduce(fn ($subtotal, $product) => $subtotal + ($product['quantity'] * $product['price']), 0);
        $totalDiscount = $selectedProducts->reduce(fn ($total, $product) => $total + ($product['discount'] * $product['quantity']), 0);

        $set('subtotal' , number_format($subtotal));
        $set('total_discount', number_format($totalDiscount));
        $set('paid', $subtotal);
        // $set('total', number_format($subtotal + ($subtotal * ($get('taxes') / 100))));

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('account.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paymentMethod.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('paid')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
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
            'index' => Pages\ListSales::route('/'),
            'create' => Pages\CreateSale::route('/create'),
            'edit' => Pages\EditSale::route('/{record}/edit'),
        ];
    }
}

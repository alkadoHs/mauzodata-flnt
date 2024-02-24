<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'account_id', 
        'name', 
        'barcode', 
        'buying_price', 
        'selling_price', 
        'unit', 
        'stock', 
        'stock_alert', 
        'expire_date',
        'whole_sale',
        'discount',
    ];


    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}

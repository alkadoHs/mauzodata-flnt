<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = ['account_id','name', 'contact'];


    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

     public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}

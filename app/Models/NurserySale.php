<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NurserySale extends Model
{
    protected $fillable = [
        'buyer_id',
        'payment_method',
        'discount_rate',
        'total_amount',
        'dispatch_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'discount_rate' => 'decimal:4',
            'total_amount' => 'decimal:2',
            'dispatch_date' => 'date',
        ];
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(Buyer::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }
}

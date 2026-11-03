<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'nursery_sale_id',
        'plant_species_id',
        'quantity_bought',
        'unit_price',
    ];

    protected function casts(): array
    {
        return [
            'quantity_bought' => 'integer',
            'unit_price' => 'decimal:2',
        ];
    }

    public function nurserySale(): BelongsTo
    {
        return $this->belongsTo(NurserySale::class);
    }

    public function plantSpecies(): BelongsTo
    {
        return $this->belongsTo(PlantSpecies::class);
    }
}
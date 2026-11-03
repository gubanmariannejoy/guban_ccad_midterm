<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantInventory extends Model
{
    protected $fillable = [
        'plant_species_id',
        'quantity_available',
    ];

    protected function casts(): array
    {
        return [
            'quantity_available' => 'integer',
        ];
    }

    public function plantSpecies(): BelongsTo
    {
        return $this->belongsTo(PlantSpecies::class);
    }
}
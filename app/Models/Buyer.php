<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Buyer extends Model
{
    protected $fillable = [
        'buyer_name',
        'contact_no',
    ];

    public function sales(): HasMany
    {
        return $this->hasMany(NurserySale::class);
    }
}
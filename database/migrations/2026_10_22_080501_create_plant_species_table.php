<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
{
    Schema::create('plant_species', function (Blueprint $table) {
        $table->id();
        $table->string('plant_name', 150);
        $table->string('botanical_name', 200)->unique();
        $table->string('pot_size', 50);
        $table->decimal('unit_price', 10, 2);
        $table->timestamps();
    });
}

    
    public function down(): void
    {
        Schema::dropIfExists('plant_species');
    }
};

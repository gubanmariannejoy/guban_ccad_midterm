<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
{
    Schema::create('plant_inventories', function (Blueprint $table) {
        $table->id();

        $table->foreignId('plant_species_id')
            ->unique()
            ->constrained('plant_species')
            ->onDelete('cascade');

        $table->unsignedInteger('quantity_available')->default(0);

        $table->timestamps();
    });
}

    
    public function down(): void
    {
        Schema::dropIfExists('plant_inventories');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
{
    Schema::create('sale_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('nursery_sale_id')
            ->constrained('nursery_sales')
            ->onDelete('cascade');

        $table->foreignId('plant_species_id')
            ->constrained('plant_species')
            ->onDelete('restrict');

        $table->unsignedInteger('quantity_bought');
        $table->decimal('unit_price', 10, 2);

        $table->timestamps();

        $table->unique([
            'nursery_sale_id',
            'plant_species_id'
        ]);
    });
}

   
    public function down(): void
    {
        Schema::dropIfExists('sale_items');
    }
};

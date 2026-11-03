<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
{
    Schema::create('nursery_sales', function (Blueprint $table) {
        $table->id();

        $table->foreignId('buyer_id')
            ->constrained('buyers')
            ->onDelete('restrict');

        $table->string('payment_method', 50);
        $table->decimal('discount_rate', 5, 4)->default(0);
        $table->decimal('total_amount', 12, 2)->default(0);
        $table->date('dispatch_date')->nullable();
        $table->string('status', 20)->default('pending');

        $table->timestamps();
    });
}

    
    public function down(): void
    {
        Schema::dropIfExists('nursery_sales');
    }
};

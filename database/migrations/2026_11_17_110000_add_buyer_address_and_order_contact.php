<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('address', 1000)->nullable();
        });

        Schema::table('nursery_sales', function (Blueprint $table): void {
            $table->string('buyer_name')->nullable();
            $table->string('buyer_contact_no', 20)->nullable();
            $table->string('buyer_address', 1000)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('nursery_sales', function (Blueprint $table): void {
            $table->dropColumn(['buyer_name', 'buyer_contact_no', 'buyer_address']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('address');
        });
    }
};

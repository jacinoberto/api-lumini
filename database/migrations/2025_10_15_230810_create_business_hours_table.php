<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('barbershop_id')->constrained('barbershops')->onDelete('cascade');
            $table->unsignedTinyInteger('day_of_week'); // 0=Domingo, 6=Sábado
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_active')->default(false);

            $table->unique(['barbershop_id', 'day_of_week']); // Garante um registro por dia da semana
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_hours');
    }
};

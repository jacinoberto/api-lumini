<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('barbershop_id')->constrained('barbershops');
            $table->string('name');
            $table->text('profile_image_url')->nullable();
            $table->text('specialties')->nullable();
            $table->boolean('is_active')->default(true);
            // Barbeiros são gerenciados pelo Owner, timestamps não são essenciais aqui.
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbers');
    }
};

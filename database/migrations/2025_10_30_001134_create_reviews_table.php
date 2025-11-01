<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->constrained('appointments')->onDelete('cascade');
            $table->foreignUuid('barbershop_id')->constrained('barbershops')->onDelete('cascade');
            $table->foreignUuid('client_id')->constrained('users')->onDelete('cascade');

            $table->integer('rating')->unsigned(); // 1 a 5
            $table->text('comment')->nullable();

            $table->timestamps();

            // Uma avaliação por agendamento
            $table->unique('appointment_id');

            // Índices
            $table->index('barbershop_id');
            $table->index('client_id');
            $table->index('rating');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};

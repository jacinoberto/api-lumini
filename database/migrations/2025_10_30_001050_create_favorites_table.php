<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('barbershop_id')->constrained('barbershops')->onDelete('cascade');
            $table->timestamps();

            // Garante que um usuário não favorite a mesma barbearia duas vezes
            $table->unique(['user_id', 'barbershop_id']);

            // Índices para performance
            $table->index('user_id');
            $table->index('barbershop_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barbershops', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->constrained('users');
            $table->foreignUuid('address_id')->constrained('addresses');
            $table->string('name');
            $table->string('company_code', 18)->nullable(); // CNPJ
            $table->text('profile_image_url')->nullable();
            $table->text('cover_image_url')->nullable();
            $table->text('biography')->nullable();
            $table->boolean('requires_prepayment')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps(); // Essencial para rastrear quando uma barbearia foi criada/atualizada
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barbershops');
    }
};

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
        Schema::table('barbershops', function (Blueprint $table) {
            // Altera a coluna address_id para permitir valores nulos
            $table->foreignUuid('address_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            // Para reverter, tornamos a coluna não-nula novamente
            // ATENÇÃO: Isso falhará se houver registros com address_id nulo no banco.
            $table->foreignUuid('address_id')->nullable(false)->change();
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Adiciona a restrição de chave estrangeira
            $table->foreign('address_id')->references('id')->on('addresses');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Importante: remove a restrição no rollback
            $table->dropForeign(['address_id']);
        });
    }
};

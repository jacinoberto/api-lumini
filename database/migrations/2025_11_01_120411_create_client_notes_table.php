<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('client_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('barbershop_id');
            $table->uuid('client_id');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('barbershop_id')->references('id')->on('barbershops')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('users')->onDelete('cascade');

            // Índice único para evitar duplicatas
            $table->unique(['barbershop_id', 'client_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('client_notes');
    }
};

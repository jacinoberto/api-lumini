<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('zip_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('zip_code', 9)->unique();
            $table->string('street');
            $table->string('area', 100)->nullable();
            $table->string('city', 100);
            $table->string('state', 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('zip_codes');
    }
};

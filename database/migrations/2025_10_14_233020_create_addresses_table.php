<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('zip_code_id')->constrained('zip_codes');
            $table->string('number', 20);
            $table->string('complement', 100)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addresses');
    }
};

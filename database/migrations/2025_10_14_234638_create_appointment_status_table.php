<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointment_status', function (Blueprint $table) {
            $table->smallIncrements('id'); // smallIncrements é perfeito para poucos registros
            $table->string('status_key', 50)->unique();
            $table->string('description', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointment_status');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('client_id')->constrained('users');
            $table->foreignUuid('barbershop_id')->constrained('barbershops');
            $table->foreignUuid('barber_id')->constrained('barbers');
            $table->foreignUuid('service_id')->constrained('services');
            $table->unsignedSmallInteger('status_id')->default(1);
            $table->foreign('status_id')->references('id')->on('appointment_status');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};

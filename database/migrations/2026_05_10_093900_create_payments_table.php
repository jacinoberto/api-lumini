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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('appointment_id')->constrained('appointments')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, cancelled, refunded
            $table->string('mp_preference_id')->nullable();
            $table->string('mp_payment_id')->nullable()->index();
            $table->string('payment_method')->nullable();   // pix, credit_card, boleto, etc.
            $table->string('payment_type')->nullable();     // credit, debit, bank_transfer, etc.
            $table->text('init_point')->nullable();         // URL checkout produção
            $table->text('sandbox_init_point')->nullable(); // URL checkout sandbox
            $table->json('gateway_response')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};

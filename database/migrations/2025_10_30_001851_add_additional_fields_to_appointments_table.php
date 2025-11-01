<?php
// database/migrations/2025_10_30_xxxxxx_add_additional_fields_to_appointments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            // Preço do serviço no momento do agendamento
            if (!Schema::hasColumn('appointments', 'price')) {
                $table->decimal('price', 10, 2)->nullable()->after('service_id');
            }

            // Observações do cliente
            if (!Schema::hasColumn('appointments', 'notes')) {
                $table->text('notes')->nullable()->after('price');
            }

            // Dados de cancelamento
            if (!Schema::hasColumn('appointments', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('status_id');
            }

            if (!Schema::hasColumn('appointments', 'cancellation_reason')) {
                $table->text('cancellation_reason')->nullable()->after('cancelled_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'price')) {
                $table->dropColumn('price');
            }

            if (Schema::hasColumn('appointments', 'notes')) {
                $table->dropColumn('notes');
            }

            if (Schema::hasColumn('appointments', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }

            if (Schema::hasColumn('appointments', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
        });
    }
};

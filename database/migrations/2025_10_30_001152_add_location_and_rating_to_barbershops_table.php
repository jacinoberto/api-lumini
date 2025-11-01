<?php
// database/migrations/2025_10_30_001152_add_location_and_rating_to_barbershops_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            // Verifica se a coluna não existe antes de adicionar
            if (!Schema::hasColumn('barbershops', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }

            if (!Schema::hasColumn('barbershops', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }

            if (!Schema::hasColumn('barbershops', 'rating_average')) {
                $table->decimal('rating_average', 2, 1)->default(0);
            }

            if (!Schema::hasColumn('barbershops', 'rating_count')) {
                $table->integer('rating_count')->unsigned()->default(0);
            }

            if (!Schema::hasColumn('barbershops', 'requires_prepayment')) {
                $table->boolean('requires_prepayment')->default(false);
            }
        });
    }

    public function down(): void
    {
        Schema::table('barbershops', function (Blueprint $table) {
            if (Schema::hasColumn('barbershops', 'latitude')) {
                $table->dropColumn('latitude');
            }

            if (Schema::hasColumn('barbershops', 'longitude')) {
                $table->dropColumn('longitude');
            }

            if (Schema::hasColumn('barbershops', 'rating_average')) {
                $table->dropColumn('rating_average');
            }

            if (Schema::hasColumn('barbershops', 'rating_count')) {
                $table->dropColumn('rating_count');
            }

            if (Schema::hasColumn('barbershops', 'requires_prepayment')) {
                $table->dropColumn('requires_prepayment');
            }
        });
    }
};

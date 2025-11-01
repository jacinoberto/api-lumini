<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('is_active');
            $table->index('order');
        });

        Schema::table('barbers', function (Blueprint $table) {
            $table->integer('order')->default(0)->after('is_active');
            $table->index('order');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['order']);
            $table->dropColumn('order');
        });

        Schema::table('barbers', function (Blueprint $table) {
            $table->dropIndex(['order']);
            $table->dropColumn('order');
        });
    }
};

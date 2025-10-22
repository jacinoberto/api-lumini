<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AppointmentStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('appointment_status')->insert([
            ['id' => 1, 'status_key' => 'CONFIRMED', 'description' => 'Confirmado'],
            ['id' => 2, 'status_key' => 'COMPLETED', 'description' => 'Concluído'],
            ['id' => 3, 'status_key' => 'CANCELLED_BY_CLIENT', 'description' => 'Cancelado pelo Cliente'],
            ['id' => 4, 'status_key' => 'CANCELLED_BY_OWNER', 'description' => 'Cancelado pela Barbearia'],
        ]);
    }
}

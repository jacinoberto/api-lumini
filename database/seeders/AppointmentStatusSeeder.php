<?php
// database/seeders/AppointmentStatusSeeder.php

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
        $statuses = [
            ['id' => 1, 'status_key' => 'PENDING', 'description' => 'Aguardando Confirmação'],
            ['id' => 2, 'status_key' => 'CONFIRMED', 'description' => 'Confirmado'],
            ['id' => 3, 'status_key' => 'COMPLETED', 'description' => 'Concluído'],
            ['id' => 4, 'status_key' => 'CANCELLED_BY_CLIENT', 'description' => 'Cancelado pelo Cliente'],
            ['id' => 5, 'status_key' => 'CANCELLED_BY_OWNER', 'description' => 'Cancelado pela Barbearia'],
            ['id' => 6, 'status_key' => 'NO_SHOW', 'description' => 'Cliente não compareceu'],
        ];

        foreach ($statuses as $status) {
            DB::table('appointment_status')->updateOrInsert(
                ['id' => $status['id']],
                $status
            );
        }
    }
}

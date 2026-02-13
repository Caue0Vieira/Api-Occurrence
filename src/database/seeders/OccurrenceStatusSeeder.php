<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class OccurrenceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'reported',
                'name' => 'Reportada',
                'is_final' => false,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'in_progress',
                'name' => 'Em Atendimento',
                'is_final' => false,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'resolved',
                'name' => 'Resolvida',
                'is_final' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'cancelled',
                'name' => 'Cancelada',
                'is_final' => true,
            ],
        ];

        foreach ($statuses as $status) {
            DB::table('occurrence_status')->insert([
                'id' => $status['id'],
                'code' => $status['code'],
                'name' => $status['name'],
                'is_final' => $status['is_final'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}


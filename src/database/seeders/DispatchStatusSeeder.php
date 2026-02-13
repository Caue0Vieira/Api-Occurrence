<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class DispatchStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'assigned',
                'name' => 'Atribuído',
                'is_active' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'en_route',
                'name' => 'A Caminho',
                'is_active' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'on_site',
                'name' => 'No Local',
                'is_active' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'closed',
                'name' => 'Encerrado',
                'is_active' => false,
            ],
        ];

        foreach ($statuses as $status) {
            DB::table('dispatch_status')->insert([
                'id' => $status['id'],
                'code' => $status['code'],
                'name' => $status['name'],
                'is_active' => $status['is_active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}


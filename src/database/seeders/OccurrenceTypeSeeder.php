<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class OccurrenceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $types = [
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'incendio_urbano',
                'name' => 'Incêndio Urbano',
                'category' => 'incêndio',
                'active' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'resgate_veicular',
                'name' => 'Resgate Veicular',
                'category' => 'resgate',
                'active' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'atendimento_pre_hospitalar',
                'name' => 'Atendimento Pré-Hospitalar',
                'category' => 'médico',
                'active' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'salvamento_aquatico',
                'name' => 'Salvamento Aquático',
                'category' => 'resgate',
                'active' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'falso_chamado',
                'name' => 'Falso Chamado',
                'category' => 'outros',
                'active' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'vazamento_gas',
                'name' => 'Vazamento de Gás',
                'category' => 'material_perigoso',
                'active' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'queda_arvore',
                'name' => 'Queda de Árvore',
                'category' => 'ambiental',
                'active' => true,
            ],
            [
                'id' => Uuid::uuid7()->toString(),
                'code' => 'incendio_florestal',
                'name' => 'Incêndio Florestal',
                'category' => 'incêndio',
                'active' => true,
            ],
        ];

        foreach ($types as $type) {
            DB::table('occurrence_types')->insert([
                'id' => $type['id'],
                'code' => $type['code'],
                'name' => $type['name'],
                'category' => $type['category'],
                'active' => $type['active'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

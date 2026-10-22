<?php

namespace Database\Seeders;

use Database\Seeders\Spatie\EgapRolesPermissionsSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
//            ImportConfigSeeder::class,
//
//            ImportNetTermUsersSeeder::class,
//            ImportNetTermCursosSeeder::class,
//            ImportNetTermCursoAlunosSeeder::class,
//            ImportNetTermCursoFrequenciaSeeder::class,
//            ImportNetTermCursoCertificadosSeeder::class,
//
//            ImportEmeronUsersSeeder::class,
//            ImportEmeronCursosSeeder::class,
//            ImportEmeronCursoAlunosSeeder::class,

            //EGAP
            //MobileApiTestUsersSeeder::class,
            //AvaliacaoServicosGeraisSeeder::class,
            EgapRolesPermissionsSeeder::class,
        ]);
    }
}

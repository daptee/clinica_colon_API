<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('user_types')->delete();

		$user_types = array(
			array('name' => 'Administrador'),
			array('name' => 'Profesional'),
			array('name' => 'Paciente'),
		);

		DB::table('user_types')->insert($user_types);
    }
}

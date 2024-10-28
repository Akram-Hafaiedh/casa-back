<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            ['name' => 'Developer', 'description' => 'Administrator role with full permissions'],
            ['name' => 'Administrator', 'description' => 'Manager Employee role with full permissions'],
            ['name' => 'Employee', 'description' => 'Employee role with basic permissions'],
        ]);
    }
}


<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'Administrator')->first()->id
        ]);

        DB::table('users')->insert([
            'name' => 'Akram',
            'email' => 'dev@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'Developer')->first()->id
        ]);

        DB::table('users')->insert([
            'name' => 'Employee',
            'email' => 'employee@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'Employee')->first()->id
        ]);
    }
}

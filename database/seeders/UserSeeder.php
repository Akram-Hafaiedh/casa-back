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
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'Administrator')->first()->id
        ]);

        DB::table('users')->insert([
            'first_name' => 'Akram',
            'last_name' => 'Hafaiedh',
            'email' => 'dev@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'Developer')->first()->id
        ]);

        DB::table('users')->insert([
            'first_name' => 'Employee',
            'last_name' => 'User',
            'email' => 'employee@example.com',
            'password' => Hash::make('password'),
            'role_id' => Role::where('name', 'Employee')->first()->id
        ]);
    }
}

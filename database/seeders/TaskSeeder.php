<?php

namespace Database\Seeders;

use App\Models\TaskStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $status = ['Pending', 'In Progress', 'Completed', 'Archived'];
        $priorities = ['Urgent', 'High', 'Normal', 'Low', 'Very Low'];
        
        foreach ($status as $status) {
            TaskStatus::firstOrCreate(['name' => $status]);
        }

        foreach ($priorities as $priority) {
            TaskStatus::firstOrCreate(['name' => $priority]);
        }
    }
}

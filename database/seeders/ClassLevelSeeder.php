<?php

namespace Database\Seeders;

use App\Models\ClassLevel;
use Illuminate\Database\Seeder;

class ClassLevelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // CBC Grade Levels
        for ($i = 1; $i <= 12; $i++) {
            ClassLevel::create(['name' => "Grade $i"]);
        }
        
        // 8-4-4 Primary Levels
        for ($i = 1; $i <= 8; $i++) {
            ClassLevel::create(['name' => "Standard $i"]);
        }
        
        // 8-4-4 Secondary Levels
        for ($i = 1; $i <= 4; $i++) {
            ClassLevel::create(['name' => "Form $i"]);
        }
    }
}

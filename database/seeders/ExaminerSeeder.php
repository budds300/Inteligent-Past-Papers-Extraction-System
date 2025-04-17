<?php

namespace Database\Seeders;

use App\Models\Examiner;
use Illuminate\Database\Seeder;

class ExaminerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $examiners = [
            'KNEC',
            'School Based',
            'Joint Mock Examination',
            'County Evaluation',
            'Regional Assessment',
            'KCPE',
            'KCSE',
            'KPSEA',
        ];

        foreach ($examiners as $examiner) {
            Examiner::create(['name' => $examiner]);
        }
    }
}

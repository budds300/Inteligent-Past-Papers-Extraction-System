<?php

namespace Database\Seeders;

use App\Models\PaperType;
use Illuminate\Database\Seeder;

class PaperTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $paperTypes = [
            'Mid-term 1',
            'Mid-term 2',
            'Mid-term 3',
            'Paper 1',
            'Paper 2',
            'Paper 3',
            'Theory Paper',
            'Practical Paper',
            'Multiple Choice',
            'Structured Questions',
            'Essay Paper',
            'Mid-Term Exam',
            'End Term Exam',
            'Opening Exam',
            'CAT',
            'Quiz',
        ];

        foreach ($paperTypes as $paperType) {
            PaperType::create(['name' => $paperType]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Term;
use Illuminate\Database\Seeder;

class TermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $terms = [
            'Term 1',
            'Term 2',
            'Term 3',
            'Semester 1',
            'Semester 2',
        ];

        foreach ($terms as $term) {
            Term::create(['name' => $term]);
        }
    }
}

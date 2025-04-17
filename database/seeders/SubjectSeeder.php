<?php

namespace Database\Seeders;

use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            'Mathematics',
            'English',
            'Kiswahili',
            'Science',
            'Social Studies',
            'Biology',
            'Chemistry',
            'Physics',
            'History',
            'Geography',
            'Computer Studies',
            'Business Studies',
            'Agriculture',
            'Religious Education',
            'Home Science',
            'Art and Design',
            'Music',
            'Physical Education',
            'Integrated Science',
        ];

        foreach ($subjects as $subject) {
            Subject::create(['name' => $subject]);
        }
    }
}

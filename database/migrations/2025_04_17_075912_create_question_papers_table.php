<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('question_papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('examiner_id')->nullable()->constrained('examiners')->nullOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained('subjects')->nullOnDelete();
            $table->foreignId('class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->foreignId('curriculum_id')->nullable()->constrained('curriculums')->nullOnDelete();
            $table->foreignId('paper_type_id')->nullable()->constrained('paper_types')->nullOnDelete();
            $table->foreignId('term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->integer('year')->nullable();
            $table->string('title')->nullable();
            $table->string('original_file_path');
            $table->string('original_file_name');
            $table->string('original_file_type');
            $table->boolean('processing_complete')->default(false);
            $table->text('processing_error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_papers');
    }
};

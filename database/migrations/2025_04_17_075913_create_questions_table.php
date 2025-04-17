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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_paper_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('questions')->cascadeOnDelete();
            $table->text('content');
            $table->string('question_number')->nullable();
            $table->decimal('marks', 8, 2)->nullable();
            $table->integer('nesting_level')->default(0); // 0 = main question, 1 = sub-question, 2 = sub-sub-question
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};

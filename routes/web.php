<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\QuestionPaperController;

Route::get('/', function () {
    return redirect()->route('question-papers.index');
});
Route::get('question-papers/create', [QuestionPaperController::class, 'create'])->name('question_papers.create');
Route::get('question-papers/{id}', [QuestionPaperController::class, 'show'])->name('question_papers.show');

Route::resource('question-papers', QuestionPaperController::class);
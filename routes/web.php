<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileUploadController;
use App\Http\Controllers\QuestionPaperController;

Route::get('/', function () {
    return redirect()->route('question-papers.index');
});

Route::resource('question-papers', QuestionPaperController::class);
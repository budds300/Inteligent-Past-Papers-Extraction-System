<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\QuestionPaperController;

Route::post('/api/question-papers/upload', [QuestionPaperController::class, 'apiUpload'])
    ->name('api.question-papers.upload');
    
Route::get('/api/question-papers/{id}/status', [QuestionPaperController::class, 'apiStatus'])
    ->name('api.question-papers.status');
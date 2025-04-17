<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessQuestionPaperJob;
use App\Models\QuestionPaper;
use App\Jobs\ProcessQuestionPaper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class QuestionPaperController extends Controller
{
    /**
     * Display a listing of question papers.
     */
    public function index()
    {
        $questionPapers = QuestionPaper::with(['examiner', 'subject', 'classLevel', 'curriculum', 'paperType', 'term'])
            ->latest()
            ->paginate(10);
            
        return view('question_papers.index', compact('questionPapers'));
    }

    /**
     * Show the form for uploading a new question paper.
     */
    public function create()
    {
        return view('question_papers.create');
    }

    /**
     * Store a newly uploaded question paper.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:50000|mimes:zip,pdf,docx,doc,jpeg,jpg,png',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $file = $request->file('file');
        $originalFileName = $file->getClientOriginalName();
        $fileType = $this->determineFileType($file->getClientOriginalExtension());
        
        // Store file
        $path = $file->store('question_papers');
        
        // Create question paper record
        $questionPaper = QuestionPaper::create([
            'title' => $request->title ?? $originalFileName,
            'original_file_path' => $path,
            'original_file_name' => $originalFileName,
            'original_file_type' => $fileType,
            'processing_complete' => false,
        ]);
        
        // Dispatch job to process the file
        ProcessQuestionPaperJob::dispatch($questionPaper);
        
        return redirect()->route('question_papers.show', $questionPaper)
            ->with('success', 'Question paper uploaded successfully. Processing started in the background.');
    }

    /**
     * Display the specified question paper.
     */
    public function show(QuestionPaper $questionPaper)
    {
        $questionPaper->load([
            'examiner', 
            'subject', 
            'classLevel', 
            'curriculum', 
            'paperType', 
            'term',
            'questions.children.answer',
            'questions.answer',
            'questions.images',
            'images'
        ]);
        
        return view('question_papers.show', compact('questionPaper'));
    }
    
    /**
     * API endpoint to upload a question paper.
     */
    public function apiUpload(Request $request)
    {
        // Debug information
        \Log::debug('Request data:', $request->all());
        \Log::debug('Files:', $request->allFiles());
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|max:50000|mimes:zip,pdf,docx,doc,jpeg,jpg,png',
            'title' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed: ' . json_encode($validator->errors()));
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $originalFileName = $file->getClientOriginalName();
        $fileType = $this->determineFileType($file->getClientOriginalExtension());
        
        \Log::debug('File details before storage:', [
            'original_name' => $originalFileName,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'file_type' => $fileType
        ]);
        
        // Check storage directory permissions first
        $storageDir = storage_path('app/question_papers');
        if (!is_dir($storageDir)) {
            try {
                mkdir($storageDir, 0755, true);
                \Log::debug('Created storage directory: ' . $storageDir);
            } catch (\Exception $e) {
                \Log::error('Failed to create storage directory: ' . $e->getMessage());
            }
        }
        
        \Log::debug('Storage directory exists: ' . (is_dir($storageDir) ? 'Yes' : 'No'));
        \Log::debug('Storage directory writable: ' . (is_writable($storageDir) ? 'Yes' : 'No'));
        
        // Try direct file saving approach
        try {
            $filename = uniqid() . '_' . $originalFileName;
            $path = 'question_papers/' . $filename;
            $fullStoragePath = storage_path('app/' . $path);
            
            // Move the uploaded file directly
            if (move_uploaded_file($file->getRealPath(), $fullStoragePath)) {
                \Log::debug('File moved successfully to: ' . $fullStoragePath);
            } else {
                \Log::error('Failed to move uploaded file. Permissions issue likely.');
                
                // Fallback to Laravel's store method
                $path = $file->store('question_papers');
                $fullStoragePath = storage_path('app/' . $path);
                \Log::debug('Fallback method used. File path: ' . $path);
            }
        } catch (\Exception $e) {
            \Log::error('Error saving file: ' . $e->getMessage());
            
            // Last resort - try Storage facade
            try {
                $path = 'question_papers/' . uniqid() . '_' . $originalFileName;
                \Storage::put($path, file_get_contents($file->getRealPath()));
                $fullStoragePath = storage_path('app/' . $path);
                \Log::debug('Storage facade method used. File path: ' . $path);
            } catch (\Exception $e2) {
                \Log::error('All file storage methods failed: ' . $e2->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to save uploaded file due to storage issues.'
                ], 500);
            }
        }
        
        // Verify file was stored successfully
        \Log::debug('File exists check: ' . (file_exists($fullStoragePath) ? 'Yes' : 'No'));
        \Log::debug('File size: ' . (file_exists($fullStoragePath) ? filesize($fullStoragePath) : 'N/A'));
        
        // Create question paper record
        $questionPaper = QuestionPaper::create([
            'title' => $request->title ?? $originalFileName,
            'original_file_path' => $path,
            'original_file_name' => $originalFileName,
            'original_file_type' => $fileType,
            'processing_complete' => false,
        ]);
        
        \Log::debug('Created question paper record:', [
            'id' => $questionPaper->id,
            'file_path' => $questionPaper->original_file_path,
            'file_type' => $questionPaper->original_file_type
        ]);
        
        // Dispatch job to process the file
        ProcessQuestionPaperJob::dispatch($questionPaper);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Question paper uploaded successfully. Processing started in the background.',
            'data' => [
                'id' => $questionPaper->id,
                'title' => $questionPaper->title,
                'status_url' => route('api.question-papers.status', $questionPaper->id)
            ]
        ], 200);
    }
    
    /**
     * API endpoint to check processing status.
     */
    public function apiStatus($id)
    {
        $questionPaper = QuestionPaper::findOrFail($id);
        
        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $questionPaper->id,
                'title' => $questionPaper->title,
                'processing_complete' => $questionPaper->processing_complete,
                'processing_error' => $questionPaper->processing_error,
                'metadata' => [
                    'examiner' => $questionPaper->examiner->name ?? null,
                    'subject' => $questionPaper->subject->name ?? null,
                    'class' => $questionPaper->classLevel->name ?? null,
                    'curriculum' => $questionPaper->curriculum->name ?? null,
                    'paper_type' => $questionPaper->paperType->name ?? null,
                    'term' => $questionPaper->term->name ?? null,
                    'year' => $questionPaper->year,
                ],
                'question_count' => $questionPaper->allQuestions()->count(),
            ]
        ], 200);
    }

    /**
     * Determine the file type from extension.
     */
    private function determineFileType($extension)
    {
        $extension = strtolower($extension);
        if ($extension === 'pdf') {
            return 'pdf';
        } elseif (in_array($extension, ['docx', 'doc'])) {
            return 'docx';
        } elseif (in_array($extension, ['jpg', 'jpeg', 'png'])) {
            return 'image';
        } elseif ($extension === 'zip') {
            return 'zip';
        } else {
            return 'unknown';
        }
    }
}
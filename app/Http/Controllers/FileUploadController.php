<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessDocumentJob;
use App\Models\ClassLevel;
use App\Models\QuestionPaper;
use App\Models\Examiner;
use App\Models\Subject;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class FileUploadController extends Controller
{
    /**
     * Display upload form
     */
    public function index()
    {
        return view('uploads.index');
    }

    /**
     * Handle the incoming file upload
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:50000',
            'examiner' => 'required|string',
            'subject' => 'required|string',
            'class' => 'required|string',
            'term' => 'required|string',
            'year' => 'required|numeric',
            'curriculum' => 'required|in:CBC,8-4-4',
            'paper_type' => 'required|string'
        ]);

        // Create question paper record
        $questionPaper = QuestionPaper::create([
            'examiner_id' => $this->findOrCreateExaminer($request->examiner),
            'subject_id' => $this->findOrCreateSubject($request->subject),
            'class_id' => $this->findOrCreateClass($request->class),
            'term' => $request->term,
            'year' => $request->year,
            'curriculum' => $request->curriculum,
            'paper_type' => $request->paper_type,
            'status' => 'pending'
        ]);

        // Generate a unique folder name for this upload
        $folderName = 'uploads/' . Str::uuid();
        $originalName = $request->file('file')->getClientOriginalName();

        // Store the uploaded file
        $path = $request->file('file')->storeAs($folderName, $originalName);

        // Process based on file type
        $extension = strtolower($request->file('file')->getClientOriginalExtension());

        if ($extension === 'zip') {
            $this->processZipFile($path, $folderName, $questionPaper->id);
        } else {
            // Queue single file for processing
            ProcessDocumentJob::dispatch($path, $questionPaper->id, $extension);
        }

        return redirect()->route('uploads.status', $questionPaper->id)
            ->with('success', 'File uploaded successfully and queued for processing');
    }

    /**
     * Display processing status
     */
    public function status($id)
    {
        $questionPaper = QuestionPaper::findOrFail($id);
        return view('uploads.status', compact('questionPaper'));
    }

    /**
     * Process zip file contents
     */
    private function processZipFile($zipPath, $extractPath, $questionPaperId)
    {
        $zip = new ZipArchive;
        $fullZipPath = Storage::path($zipPath);
        $extractTo = Storage::path($extractPath);

        if ($zip->open($fullZipPath) === TRUE) {
            $zip->extractTo($extractTo);
            $zip->close();

            // Process each extracted file
            $files = scandir($extractTo);
            foreach ($files as $file) {
                if ($file === '.' || $file === '..') continue;

                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $filePath = $extractPath . '/' . $file;

                if (in_array($extension, ['pdf', 'docx', 'jpg', 'png', 'doc'])) {
                    // Ensure file path is relative to storage/app
                    ProcessDocumentJob::dispatch($filePath, $questionPaperId, $extension);
                }
            }
        } else {
            // Handle zip extraction failure
            // Optionally, log or notify user
        }
    }

    /**
     * Find or create examiner
     */
    private function findOrCreateExaminer($name)
    {
        $examiner = Examiner::firstOrCreate(['name' => $name]);
        return $examiner->id;
    }

    /**
     * Find or create subject
     */
    private function findOrCreateSubject($name)
    {
        $subject = Subject::firstOrCreate(['name' => $name]);
        return $subject->id;
    }

    /**
     * Find or create class
     */
    private function findOrCreateClass($name)
    {
        $class = ClassLevel::firstOrCreate(['name' => $name]);
        return $class->id;
    }
}
<?php

namespace App\Services;

use App\Models\QuestionPaper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Spatie\PdfToText\Pdf;
use PhpOffice\PhpWord\IOFactory as PhpWordIOFactory;
use App\Services\OpenAIService;
use App\Services\DeepSeekService;
use Ottosmops\Pdftotext\Pdftotext;
use Smalot\PdfParser\Parser;

class FileProcessorService
{
    protected $openAIService;
    protected $deepSeekService;

    public function __construct(OpenAIService $openAIService, DeepSeekService $deepSeekService)
    {
        $this->openAIService = $openAIService;
        $this->deepSeekService = $deepSeekService;
    }

    public function processFile(QuestionPaper $questionPaper)
    {
        try {
            $filePath = $questionPaper->original_file_path;
            $fileType = $questionPaper->original_file_type;
            $textContent = null;
            $extractedFiles = [];

            Log::info("Starting processing question paper ID: {$questionPaper->id}");
            Log::debug("Processing file details:", [
                'file_path' => $filePath,
                'file_type' => $fileType,
                'file_name' => $questionPaper->original_file_name
            ]);
            
            // Check if file exists in storage
            $fullStoragePath = storage_path('app/' . $filePath);
            if (!file_exists($fullStoragePath)) {
                Log::error("File not found at expected path: {$fullStoragePath}");
                // Try to find the file in alternative locations
                $possiblePaths = [
                    $filePath,
                    storage_path('app/' . $filePath),
                    storage_path('app/public/' . $filePath),
                    public_path('storage/' . $filePath)
                ];
                
                $foundPath = null;
                foreach ($possiblePaths as $path) {
                    if (file_exists($path)) {
                        $foundPath = $path;
                        Log::debug("Found file at alternative path: {$path}");
                        break;
                    }
                }
                
                if (!$foundPath) {
                    throw new \Exception("File not found at any expected location");
                }
                
                $fullStoragePath = $foundPath;
            } else {
                Log::debug("File found at expected path: {$fullStoragePath}");
            }

            if ($fileType === 'zip') {
                $extractedFiles = $this->extractZipFile($filePath);
                foreach ($extractedFiles as $extractedFile) {
                    $this->processExtractedFile($extractedFile, $questionPaper);
                }
                $questionPaper->update(['processing_complete' => true]);
                Log::info("Completed processing question paper ID: {$questionPaper->id}");
                return true;
            } elseif ($fileType === 'pdf') {
                $textContent = $this->extractPdfText($filePath);
            } elseif ($fileType === 'docx') {
                $textContent = $this->extractDocxText($filePath);
            } elseif ($fileType === 'image') {
                $this->processImage($filePath, $questionPaper);
                $questionPaper->update(['processing_complete' => true]);
                Log::info("Completed processing question paper ID: {$questionPaper->id}");
                return true;
            } else {
                throw new \Exception("Unsupported file type: {$fileType}");
            }

            if ($textContent) {
                Log::debug("Successfully extracted text content, length: " . strlen($textContent));
                $extractedData = $this->extractDataWithAI($textContent);
                if ($extractedData) {
                    $this->saveExtractedData($extractedData, $questionPaper);
                }
            } else {
                Log::warning("No text content was extracted from the file");
            }

            $questionPaper->update(['processing_complete' => true]);
            Log::info("Completed processing question paper ID: {$questionPaper->id}");
            return true;
        } catch (\Exception $e) {
            Log::error('File processing error: ' . $e->getMessage());
            Log::error('Exception trace: ' . $e->getTraceAsString());
            $questionPaper->update([
                'processing_complete' => true,
                'processing_error' => $e->getMessage()
            ]);
            Log::info("Completed processing question paper ID: {$questionPaper->id}");
            return false;
        }
    }

    protected function extractZipFile($filePath)
    {
        $extractPath = storage_path('app/extracted_' . uniqid());
        $zip = new ZipArchive;
        $fullPath = storage_path('app/' . $filePath);
       
        Log::debug("Attempting to open ZIP: {$fullPath}");
       
        if (!file_exists($fullPath)) {
            Log::error("ZIP file does not exist: {$fullPath}");
            throw new \Exception("ZIP file not found at {$fullPath}");
        }
       
        $result = $zip->open($fullPath);
        if ($result === TRUE) {
            Log::debug("Creating extract path: {$extractPath}");
            if (!file_exists($extractPath)) {
                mkdir($extractPath, 0755, true);
            }
           
            $extractResult = $zip->extractTo($extractPath);
            if (!$extractResult) {
                Log::error("Failed to extract ZIP to {$extractPath}");
                throw new \Exception("Failed to extract ZIP contents");
            }
           
            $zip->close();
           
            $extractedFiles = [];
            $this->scanDirectory($extractPath, $extractedFiles);
            return $extractedFiles;
        } else {
            Log::error("Failed to open ZIP file. Error code: {$result}");
            throw new \Exception("Failed to open ZIP file. Error code: {$result}");
        }
    }
    protected function scanDirectory($dir, &$results)
    {
        $files = scandir($dir);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $path = $dir . '/' . $file;

            if (is_dir($path)) {
                $this->scanDirectory($path, $results);
            } else {
                $extension = pathinfo($path, PATHINFO_EXTENSION);
                $results[] = [
                    'path' => $path,
                    'type' => $this->determineFileType($extension)
                ];
            }
        }
    }

    protected function determineFileType($extension)
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

    protected function processExtractedFile($fileInfo, QuestionPaper $parentPaper)
    {
        $path = $fileInfo['path'];
        $type = $fileInfo['type'];

        if ($type === 'pdf') {
            $textContent = $this->extractPdfText($path);
            $extractedData = $this->extractDataWithAI($textContent);
            if ($extractedData) {
                $this->saveExtractedData($extractedData, $parentPaper);
            }
        } elseif ($type === 'docx') {
            $textContent = $this->extractDocxText($path);
            $extractedData = $this->extractDataWithAI($textContent);
            if ($extractedData) {
                $this->saveExtractedData($extractedData, $parentPaper);
            }
        } elseif ($type === 'image') {
            $this->processImage($path, $parentPaper);
        }
    }

     
    protected function extractPdfText($filePath)
    {
        $fullPath = is_file($filePath) ? $filePath : storage_path('app/' . $filePath);
        try {
            // Try using ottosmops/pdftotext first
            try {
                $pdfToText = new Pdftotext($fullPath);
                $text = $pdfToText->getText();
                
                if (!empty(trim($text))) {
                    Log::debug("Successfully extracted text using ottosmops/pdftotext, length: " . strlen($text));
                    return $text;
                }
            } catch (\Exception $innerException) {
                Log::warning('ottosmops/pdftotext extraction failed: ' . $innerException->getMessage() . '. Falling back to Smalot PDF Parser.');
            }
            
            // Fallback to Smalot PDF Parser if ottosmops fails
            $parser = new Parser();
            $pdf = $parser->parseFile($fullPath);
            $text = $pdf->getText();
            
            Log::debug("Successfully extracted text using Smalot PDF Parser, length: " . strlen($text));
            return $text;
        } catch (\Exception $e) {
            Log::error('PDF extraction error: ' . $e->getMessage());
            throw $e;
        }
    }
    protected function extractDocxText($filePath)
    {
            // Try multiple possible paths
    $possiblePaths = [
        $filePath,
        storage_path('app/' . $filePath),
        storage_path('app/public/' . $filePath),
        public_path('storage/' . $filePath)
    ];
    
    $foundPath = null;
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $foundPath = $path;
            Log::debug("Found DOCX file at: {$path}");
            break;
        }
    }
    
    if (!$foundPath) {
        Log::error("DOCX file not found at any expected location. Original path: {$filePath}");
        throw new \Exception("Cannot find archive file");
    }
    
    try {
        $phpWord = PhpWordIOFactory::load($foundPath);
        $text = '';

        foreach ($phpWord->getSections() as $section) {
                foreach ($section->getElements() as $element) {
                    if (method_exists($element, 'getText')) {
                        $text .= $element->getText() . ' ';
                    } elseif (method_exists($element, 'getElements')) {
                        foreach ($element->getElements() as $childElement) {
                            if (method_exists($childElement, 'getText')) {
                                $text .= $childElement->getText() . ' ';
                            }
                        }
                    }
                }
            }

            return $text;
        } catch (\Exception $e) {
            Log::error('DOCX extraction error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function processImage($imagePath, QuestionPaper $questionPaper)
    {
        $relativePath = str_replace(storage_path('app/'), '', $imagePath);
        if (!str_starts_with($relativePath, 'public/')) {
            $newPath = 'public/images/' . basename($imagePath);
            Storage::copy($relativePath, $newPath);
            $relativePath = $newPath;
        }

        $imageUrl = asset(Storage::url($relativePath));
        $imageData = $this->openAIService->identifyImages($imageUrl);

        if ($imageData && isset($imageData['type'])) {
            if ($imageData['type'] === 'question') {
                $question = $questionPaper->allQuestions()->create([
                    'content' => $imageData['extracted_text'] ?? 'Image-based question',
                    'question_number' => 'img-' . time(),
                    'nesting_level' => 0,
                ]);
                $question->images()->create([
                    'file_path' => $relativePath,
                    'caption' => 'Question image'
                ]);
            } elseif ($imageData['type'] === 'answer') {
                $question = $questionPaper->allQuestions()->create([
                    'content' => 'Question for image-based answer',
                    'question_number' => 'img-ans-' . time(),
                    'nesting_level' => 0,
                ]);
                $answer = $question->answer()->create([
                    'content' => $imageData['extracted_text'] ?? 'Image-based answer',
                ]);
                $answer->images()->create([
                    'file_path' => $relativePath,
                    'caption' => 'Answer image'
                ]);
            } else {
                $questionPaper->images()->create([
                    'file_path' => $relativePath,
                    'caption' => $imageData['extracted_text'] ?? 'Exam paper image'
                ]);
            }
        } else {
            $questionPaper->images()->create([
                'file_path' => $relativePath,
                'caption' => 'Exam paper image'
            ]);
        }
    }

    protected function extractDataWithAI($textContent)
    {
        // Specifically use OpenAI for extraction
        $extractedData = $this->openAIService->extractQuestionData($textContent);
        
        if (!$extractedData) {
            Log::warning('OpenAI extraction failed or returned empty data');
        }
        
        return $extractedData;
    }

    protected function saveExtractedData($data, QuestionPaper $questionPaper)
    {
        // Update metadata if available
        if (isset($data['metadata'])) {
            $metadata = $data['metadata'];

            if (isset($metadata['examiner']) && $metadata['examiner']) {
                $examiner = \App\Models\Examiner::firstOrCreate(['name' => $metadata['examiner']]);
                $questionPaper->examiner_id = $examiner->id;
            }
            if (isset($metadata['subject']) && $metadata['subject']) {
                $subject = \App\Models\Subject::firstOrCreate(['name' => $metadata['subject']]);
                $questionPaper->subject_id = $subject->id;
            }
            if (isset($metadata['class']) && $metadata['class']) {
                $class = \App\Models\ClassLevel::firstOrCreate(['name' => $metadata['class']]);
                $questionPaper->class_id = $class->id;
            }
            if (isset($metadata['term']) && $metadata['term']) {
                $term = \App\Models\Term::firstOrCreate(['name' => $metadata['term']]);
                $questionPaper->term_id = $term->id;
            }
            if (isset($metadata['curriculum']) && $metadata['curriculum']) {
                $curriculum = \App\Models\Curriculum::firstOrCreate(['name' => $metadata['curriculum']]);
                $questionPaper->curriculum_id = $curriculum->id;
            }
            if (isset($metadata['paper_type']) && $metadata['paper_type']) {
                $paperType = \App\Models\PaperType::firstOrCreate(['name' => $metadata['paper_type']]);
                $questionPaper->paper_type_id = $paperType->id;
            }
            if (isset($metadata['year']) && $metadata['year']) {
                $questionPaper->year = $metadata['year'];
            }
            $questionPaper->save();
        }

        // Process questions and answers
        if (isset($data['questions']) && is_array($data['questions'])) {
            $questionMap = [];
            foreach ($data['questions'] as $questionData) {
                if (isset($questionData['content']) && $questionData['content']) {
                    $parentId = null;
                    if (isset($questionData['parent_question_number'])) {
                        $parentNumber = $questionData['parent_question_number'];
                        if (isset($questionMap[$parentNumber])) {
                            $parentId = $questionMap[$parentNumber];
                        }
                    }
                    $question = $questionPaper->allQuestions()->create([
                        'content' => $questionData['content'],
                        'question_number' => $questionData['question_number'] ?? null,
                        'marks' => $questionData['marks'] ?? null,
                        'nesting_level' => $questionData['nesting_level'] ?? 0,
                        'parent_id' => $parentId
                    ]);
                    if (isset($questionData['question_number'])) {
                        $questionMap[$questionData['question_number']] = $question->id;
                    }
                }
            }

            // Process answers
            if (isset($data['answers']) && is_array($data['answers'])) {
                foreach ($data['answers'] as $answerData) {
                    if (
                        isset($answerData['question_number']) &&
                        isset($questionMap[$answerData['question_number']])
                    ) {
                        $questionId = $questionMap[$answerData['question_number']];
                        $question = \App\Models\Question::find($questionId);
                        if ($question && isset($answerData['content']) && $answerData['content']) {
                            $question->answer()->create([
                                'content' => $answerData['content']
                            ]);
                        }
                    }
                }
            }
        }
    }
}
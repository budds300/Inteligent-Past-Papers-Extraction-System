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

            if ($fileType === 'zip') {
                $extractedFiles = $this->extractZipFile($filePath);
                foreach ($extractedFiles as $extractedFile) {
                    $this->processExtractedFile($extractedFile, $questionPaper);
                }
                $questionPaper->update(['processing_complete' => true]);
                return true;
            } elseif ($fileType === 'pdf') {
                $textContent = $this->extractPdfText($filePath);
            } elseif ($fileType === 'docx') {
                $textContent = $this->extractDocxText($filePath);
            } elseif ($fileType === 'image') {
                $this->processImage($filePath, $questionPaper);
                $questionPaper->update(['processing_complete' => true]);
                return true;
            } else {
                throw new \Exception("Unsupported file type: {$fileType}");
            }

            if ($textContent) {
                $extractedData = $this->extractDataWithAI($textContent);
                if ($extractedData) {
                    $this->saveExtractedData($extractedData, $questionPaper);
                }
            }

            $questionPaper->update(['processing_complete' => true]);
            return true;
        } catch (\Exception $e) {
            Log::error('File processing error: ' . $e->getMessage());
            $questionPaper->update([
                'processing_complete' => true,
                'processing_error' => $e->getMessage()
            ]);
            return false;
        }
    }

    protected function extractZipFile($filePath)
    {
        $extractPath = storage_path('app/extracted_' . uniqid());
        $zip = new ZipArchive;

        if ($zip->open(storage_path('app/' . $filePath)) === TRUE) {
            $zip->extractTo($extractPath);
            $zip->close();

            $extractedFiles = [];
            $this->scanDirectory($extractPath, $extractedFiles);
            return $extractedFiles;
        }

        throw new \Exception("Failed to extract zip file");
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
            $text = (new Pdf())
                ->setPdf($fullPath)
                ->text();

            if (empty(trim($text))) {
                $parser = new \Smalot\PdfParser\Parser();
                $pdf = $parser->parseFile($fullPath);
                $text = $pdf->getText();
            }

            return $text;
        } catch (\Exception $e) {
            Log::error('PDF extraction error: ' . $e->getMessage());
            throw $e;
        }
    }

    protected function extractDocxText($filePath)
    {
        $fullPath = is_file($filePath) ? $filePath : storage_path('app/' . $filePath);
        try {
            $phpWord = PhpWordIOFactory::load($fullPath);
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
        $extractedData = $this->openAIService->extractQuestionData($textContent);

        if (!$extractedData) {
            $extractedData = $this->deepSeekService->extractQuestionData($textContent);
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
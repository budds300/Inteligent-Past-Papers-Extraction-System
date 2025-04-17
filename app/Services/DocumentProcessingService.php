<?php

namespace App\Services;

use App\Models\QuestionPaper;
use App\Models\Question;
use App\Models\Answer;
use App\Models\Image;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser as PdfParser;
use PhpOffice\PhpWord\IOFactory as PhpWordIOFactory;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class DocumentProcessingService
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Process a document based on its type
     */
    public function processDocument($filePath, $questionPaperId, $fileType)
    {
        try {
            $questionPaper = QuestionPaper::findOrFail($questionPaperId);
            $questionPaper->update(['status' => 'processing']);
            
            $extractedText = '';
            
            switch($fileType) {
                case 'pdf':
                    $extractedText = $this->extractTextFromPdf($filePath);
                    break;
                case 'docx':
                    $extractedText = $this->extractTextFromDocx($filePath);
                    break;
                case 'doc':
                    $extractedText = $this->extractTextFromDocx($filePath);
                    break;
                case 'jpg':
                case 'png':
                    return $this->processImage($filePath, $questionPaperId);
                default:
                    throw new Exception("Unsupported file type: $fileType");
            }
            
            if (!empty($extractedText)) {
                $this->processExtractedText($extractedText, $questionPaperId);
                $questionPaper->update(['status' => 'completed']);
                return true;
            }
            
            throw new Exception("Failed to extract text from $filePath");
            
        } catch (Exception $e) {
            QuestionPaper::where('id', $questionPaperId)->update(['status' => 'failed']);
            Log::error("Document processing error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Extract text from PDF file
     */
    private function extractTextFromPdf($filePath)
    {
        $parser = new PdfParser();
        $pdf = $parser->parseFile(Storage::path($filePath));
        
        $text = '';
        foreach ($pdf->getPages() as $page) {
            $text .= $page->getText() . "\n";
        }
        
        return $text;
    }
    
    /**
     * Extract text from DOCX file
     */
    private function extractTextFromDocx($filePath)
    {
        $phpWord = PhpWordIOFactory::load(Storage::path($filePath));
        
        $text = '';
        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $text .= $element->getText() . "\n";
                }
            }
        }
        
        return $text;
    }
    
    /**
     * Process image file
     */
    private function processImage($filePath, $questionPaperId)
    {
        $questionPaper = QuestionPaper::findOrFail($questionPaperId);
        $questionPaper->update(['status' => 'processing']);
        
        // Generate a unique name for the stored image
        $storedPath = 'processed/' . basename($filePath);
        
        // Copy the image to processed directory
        Storage::copy($filePath, $storedPath);
        
        // Get image content analysis from OpenAI
        $imageContent = $this->openAIService->identifyImages(Storage::path($filePath));
        
        if ($imageContent && isset($imageContent['type']) && isset($imageContent['extracted_text'])) {
            // Store image record
            $image = new Image([
                'question_paper_id' => $questionPaperId,
                'file_path' => $storedPath,
                'content_type' => $imageContent['type'],
                'extracted_text' => $imageContent['extracted_text']
            ]);
            $image->save();
            
            // If the image contains a question or answer, process it
            if ($imageContent['type'] === 'question') {
                $this->createQuestion($imageContent['extracted_text'], $questionPaperId, null, $image->id);
            } elseif ($imageContent['type'] === 'answer') {
                // Try to match the answer to a question
                $this->processAnswerText($imageContent['extracted_text'], $questionPaperId, $image->id);
            }
            
            $questionPaper->update(['status' => 'completed']);
            return true;
        }
        
        $questionPaper->update(['status' => 'failed']);
        return false;
    }
    
    /**
     * Process extracted text using OpenAI
     */
    private function processExtractedText($text, $questionPaperId)
    {
        $extractedData = $this->openAIService->extractQuestionData($text);
        
        if (!$extractedData) {
            throw new Exception("Failed to extract data from text");
        }
        
        // Process questions
        if (isset($extractedData['questions']) && is_array($extractedData['questions'])) {
            $questionMap = [];
            
            // First pass to create all questions
            foreach ($extractedData['questions'] as $questionData) {
                $parentId = null;
                
                // If this is a sub-question, find its parent
                if ($questionData['nesting_level'] > 0) {
                    // Parse the parent question number
                    $parts = explode('.', $questionData['question_number']);
                    array_pop($parts);
                    $parentNumber = implode('.', $parts);
                    
                    if (isset($questionMap[$parentNumber])) {
                        $parentId = $questionMap[$parentNumber];
                    }
                }
                
                $question = $this->createQuestion(
                    $questionData['content'],
                    $questionPaperId,
                    $parentId,
                    null,
                    $questionData['question_number'],
                    $questionData['nesting_level']
                );
                
                $questionMap[$questionData['question_number']] = $question->id;
            }
            
            // Process answers
            if (isset($extractedData['answers']) && is_array($extractedData['answers'])) {
                foreach ($extractedData['answers'] as $answerData) {
                    if (isset($answerData['question_number']) && isset($questionMap[$answerData['question_number']])) {
                        $questionId = $questionMap[$answerData['question_number']];
                        
                        Answer::create([
                            'question_id' => $questionId,
                            'content' => $answerData['content'] ?? '',
                        ]);
                    }
                }
            }
        }
    }
    
    /**
     * Create a question record
     */
    private function createQuestion($content, $questionPaperId, $parentId = null, $imageId = null, $questionNumber = null, $nestingLevel = 0)
    {
        return Question::create([
            'question_paper_id' => $questionPaperId,
            'parent_id' => $parentId,
            'image_id' => $imageId,
            'content' => $content,
            'question_number' => $questionNumber,
            'nesting_level' => $nestingLevel
        ]);
    }
    
    /**
     * Process answer text and try to match to a question
     */
    private function processAnswerText($text, $questionPaperId, $imageId)
    {
        // Try to extract question number from answer text
        preg_match('/(?:answer|ans)?\s*(?:to|for)?\s*(?:question|q)?\s*(?:no\.?)?\s*(\d+(?:\.\d+)*)/i', $text, $matches);
        
        $questionId = null;
        
        if (!empty($matches[1])) {
            // Try to find the question by number
            $question = Question::where('question_paper_id', $questionPaperId)
                ->where('question_number', $matches[1])
                ->first();
                
            if ($question) {
                $questionId = $question->id;
            }
        }
        
        // If we couldn't find a matching question, check if there's only one question
        if (!$questionId) {
            $questions = Question::where('question_paper_id', $questionPaperId)->get();
            if ($questions->count() === 1) {
                $questionId = $questions->first()->id;
            }
        }
        
        Answer::create([
            'question_id' => $questionId,
            'content' => $text,
            'image_id' => $imageId
        ]);
    }
}
<?php

namespace App\Services;

use OpenAI;
use Illuminate\Support\Facades\Log;

class OpenAIService
{
    protected $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.api_key'));
    }

    public function extractQuestionData($text)
    {
        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI expert in exam paper extraction, specializing in Kenyan 8-4-4 and CBC curriculum formats. Extract questions, sub-questions, answers, and metadata from the provided text. Format your response as JSON with precise hierarchical relationships between questions.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract the following from this exam paper text and format as JSON:\n\n" . 
                            "1. metadata object with these fields:\n" .
                            "   - examiner (the organization that created the exam)\n" .
                            "   - subject (e.g., Mathematics, English, Science)\n" .
                            "   - class (e.g., Grade 4, Form 2, Standard 7)\n" .
                            "   - term (e.g., Term 1, Term 2, Term 3)\n" .
                            "   - year (the year the exam was created)\n" .
                            "   - curriculum (must be either 'CBC' or '8-4-4')\n" .
                            "   - paper_type (e.g., Paper 1, Paper 2, Practical)\n\n" .
                            "2. questions array with these fields for each question:\n" .
                            "   - question_number (e.g., 1, 2, 3 or 1a, 1b, etc.)\n" .
                            "   - content (the question text)\n" .
                            "   - marks (if specified, otherwise null)\n" .
                            "   - nesting_level (0 for main questions, 1 for sub-questions, 2 for sub-sub-questions)\n" .
                            "   - parent_question_number (for sub-questions, reference the parent question number, null for main questions)\n\n" .
                            "3. answers array with these fields for each answer:\n" .
                            "   - question_number (matching the question this answer belongs to)\n" .
                            "   - content (the answer text)\n\n" .
                            "Text: " . $text
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.2,
            ]);

            $content = $response->choices[0]->message->content;
            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('OpenAI API error: ' . $e->getMessage());
            return null;
        }
    }

    public function identifyImages($imageText)
    {
        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4-vision-preview',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an AI expert in identifying exam content. Determine if this image contains a question, an answer, or general content, and extract any visible text.'
                    ],
                    [
                        'role' => 'user',
                        'content' => [
                            ['type' => 'text', 'text' => 'Analyze this image from an exam paper. Is it a question, an answer, or general content? Extract any visible text. Respond in JSON format with type (question/answer/other) and extracted_text fields.'],
                            ['type' => 'image_url', 'image_url' => ['url' => $imageText]]
                        ]
                    ]
                ],
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.2,
            ]);

            $content = $response->choices[0]->message->content;
            return json_decode($content, true);
        } catch (\Exception $e) {
            Log::error('OpenAI Vision API error: ' . $e->getMessage());
            return null;
        }
    }
}
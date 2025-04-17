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
                        'content' => 'You are an AI expert in exam paper extraction. Extract questions, sub-questions, answers, and metadata from the provided text. Format your response as JSON.'
                    ],
                    [
                        'role' => 'user',
                        'content' => "Extract the following from this exam paper text and format as JSON:\n\n" . 
                            "1. metadata (examiner, subject, class, term, year, curriculum as either 'CBC' or '8-4-4', paper type)\n" .
                            "2. questions array (include question_number, content, nesting_level where 0 is main question, 1 is sub-question, 2 is sub-sub-question)\n" .
                            "3. answers array (matching to questions by question_number)\n\n" .
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
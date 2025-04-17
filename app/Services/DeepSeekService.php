<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeepSeekService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.deepseek.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.deepseek.api_key');
    }

    public function extractQuestionData($text)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'model' => 'deepseek-chat',
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
                'temperature' => 0.2,
            ]);

            $data = $response->json();
            $content = $data['choices'][0]['message']['content'];
            
            // Try to extract JSON from the response if it's not already in JSON format
            if ($this->isJson($content)) {
                return json_decode($content, true);
            } else {
                // Try to extract JSON from text response
                preg_match('/```json\s*([\s\S]*?)\s*```/', $content, $matches);
                if (!empty($matches[1])) {
                    return json_decode($matches[1], true);
                }
            }
            
            Log::error('Failed to parse DeepSeek response as JSON: ' . $content);
            return null;
        } catch (\Exception $e) {
            Log::error('DeepSeek API error: ' . $e->getMessage());
            return null;
        }
    }

    private function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

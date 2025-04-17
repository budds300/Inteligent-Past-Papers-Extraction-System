<?php

namespace App\Jobs;

use App\Models\QuestionPaper;
use App\Services\FileProcessorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessQuestionPaperJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $questionPaper;
    public $timeout = 600; // 10 minutes
    public $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(QuestionPaper $questionPaper)
    {
        $this->questionPaper = $questionPaper;
    }

    /**
     * Execute the job.
     */
    public function handle(FileProcessorService $fileProcessor): void
    {
        try {
            Log::info('Starting processing question paper ID: ' . $this->questionPaper->id);
            $fileProcessor->processFile($this->questionPaper);
            Log::info('Completed processing question paper ID: ' . $this->questionPaper->id);
        } catch (\Exception $e) {
            Log::error('Error processing question paper: ' . $e->getMessage());
            $this->questionPaper->update([
                'processing_complete' => true,
                'processing_error' => 'Job error: ' . $e->getMessage()
            ]);
            
            // Fail the job, which will trigger retries if tries > 1
            $this->fail($e);
        }
    }
}
<?php

namespace App\Jobs;

use App\Services\DocumentProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $filePath;
    protected $questionPaperId;
    protected $fileType;
    
    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;
    
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filePath, $questionPaperId, $fileType)
    {
        $this->filePath = $filePath;
        $this->questionPaperId = $questionPaperId;
        $this->fileType = $fileType;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(DocumentProcessingService $documentService)
    {
        Log::info("Processing document: {$this->filePath} for paper ID: {$this->questionPaperId}");
        
        $result = $documentService->processDocument(
            $this->filePath, 
            $this->questionPaperId, 
            $this->fileType
        );
        
        if ($result) {
            Log::info("Successfully processed document: {$this->filePath}");
        } else {
            Log::error("Failed to process document: {$this->filePath}");
        }
    }
    
    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception)
    {
        Log::error("Document processing job failed: " . $exception->getMessage());
        
        // Update question paper status to failed
        \App\Models\QuestionPaper::where('id', $this->questionPaperId)
            ->update(['status' => 'failed']);
    }
}
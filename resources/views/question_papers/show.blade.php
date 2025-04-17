@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $questionPaper->title }}</h1>
             <p class="text-sm text-gray-500">Uploaded {{ $questionPaper->created_at ? $questionPaper->created_at->diffForHumans() : 'recently' }}</p>
            </div>
            <a href="{{ route('question-papers.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                <svg class="-ml-1 mr-2 h-5 w-5 text-indigo-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                </svg>
                Back to All Papers
            </a>
        </div>

        @if (session('success'))
            <div class="rounded-md bg-green-50 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-green-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
            <div class="px-4 py-5 sm:px-6 bg-gray-50">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Question Paper Details</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Metadata and processing information.</p>
                
                @if (!$questionPaper->processing_complete)
                    <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-yellow-100 text-yellow-800">
                        <svg class="-ml-1 mr-1.5 h-4 w-4 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                        </svg>
                        Processing...
                    </div>
                @elseif ($questionPaper->processing_error)
                    <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                        <svg class="-ml-1 mr-1.5 h-4 w-4 text-red-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                        </svg>
                        Processing Error
                    </div>
                @else
                    <div class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <svg class="-ml-1 mr-1.5 h-4 w-4 text-green-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                        Processing Complete
                    </div>
                @endif
            </div>
            <div class="border-t border-gray-200">
                <dl>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">File Name</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $questionPaper->original_file_name }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">File Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ strtoupper($questionPaper->original_file_type) }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Examiner</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $questionPaper->examiner->name ?? 'Not identified' }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Subject</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $questionPaper->subject->name ?? 'Not identified' }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Class Level</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $questionPaper->classLevel->name ?? 'Not identified' }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Curriculum</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $questionPaper->curriculum->name ?? 'Not identified' }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Paper Type</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $questionPaper->paperType->name ?? 'Not identified' }}</dd>
                    </div>
                    <div class="bg-white px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Term</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $questionPaper->term->name ?? 'Not identified' }}</dd>
                    </div>
                    <div class="bg-gray-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-gray-500">Year</dt>
                        <dd class="mt-1 text-sm text-gray-900 sm:mt-0 sm:col-span-2">{{ $questionPaper->year ?? 'Not identified' }}</dd>
                    </div>
                    @if ($questionPaper->processing_error)
                    <div class="bg-red-50 px-4 py-5 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <dt class="text-sm font-medium text-red-500">Processing Error</dt>
                        <dd class="mt-1 text-sm text-red-700 sm:mt-0 sm:col-span-2">{{ $questionPaper->processing_error }}</dd>
                    </div>
                    @endif
                </dl>
            </div>
        </div>

        @if ($questionPaper->processing_complete && !$questionPaper->processing_error && $questionPaper->questions->count() > 0)
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Questions</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $questionPaper->questions->count() }} questions extracted.</p>
                </div>
                <div class="border-t border-gray-200">
                    <ul class="divide-y divide-gray-200">
                        @foreach ($questionPaper->questions as $question)
                            <li class="px-4 py-4">
                                <div class="mb-2">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                        Q{{ $question->question_number }}
                                    </span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ $question->marks ?? 0 }} marks
                                    </span>
                                </div>
                                <div class="text-sm text-gray-900 mb-2">{!! nl2br(e($question->content)) !!}</div>
                                
                                @if ($question->answer)
                                    <div class="mt-2 pl-4 border-l-4 border-green-400">
                                        <h4 class="text-sm font-medium text-gray-700">Answer:</h4>
                                        <p class="text-sm text-gray-600">{!! nl2br(e($question->answer->content)) !!}</p>
                                    </div>
                                @endif
                                
                                @if ($question->children->count() > 0)
                                    <div class="mt-4 pl-6">
                                        <h4 class="text-sm font-medium text-gray-700 mb-2">Sub-questions:</h4>
                                        <ul class="space-y-3">
                                            @foreach ($question->children as $subQuestion)
                                                <li class="bg-gray-50 p-3 rounded">
                                                    <div class="mb-1">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-2">
                                                            Q{{ $question->question_number }}{{ $subQuestion->question_number }}
                                                        </span>
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                            {{ $subQuestion->marks ?? 0 }} marks
                                                        </span>
                                                    </div>
                                                    <div class="text-sm text-gray-900 mb-2">{!! nl2br(e($subQuestion->content)) !!}</div>
                                                    
                                                    @if ($subQuestion->answer)
                                                        <div class="mt-2 pl-4 border-l-4 border-green-400">
                                                            <h4 class="text-sm font-medium text-gray-700">Answer:</h4>
                                                            <p class="text-sm text-gray-600">{!! nl2br(e($subQuestion->answer->content)) !!}</p>
                                                        </div>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @elseif ($questionPaper->processing_complete && !$questionPaper->processing_error)
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6 p-6 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No questions found</h3>
                <p class="mt-1 text-sm text-gray-500">No questions were extracted from this document.</p>
            </div>
        @elseif (!$questionPaper->processing_complete)
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6 p-6 text-center">
                <svg class="animate-spin mx-auto h-12 w-12 text-indigo-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Processing in progress</h3>
                <p class="mt-1 text-sm text-gray-500">Please wait while we extract questions and answers from this document.</p>
                <p class="mt-3 text-xs text-gray-400">This page will automatically refresh in <span id="countdown">30</span> seconds.</p>
                
                <script>
                    // Auto-refresh countdown
                    let seconds = 30;
                    const countdownEl = document.getElementById('countdown');
                    
                    const interval = setInterval(() => {
                        seconds--;
                        countdownEl.textContent = seconds;
                        
                        if (seconds <= 0) {
                            clearInterval(interval);
                            window.location.reload();
                        }
                    }, 1000);
                </script>
            </div>
        @endif

        @if ($questionPaper->images->count() > 0)
            <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6 bg-gray-50">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Images</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">{{ $questionPaper->images->count() }} images extracted.</p>
                </div>
                <div class="border-t border-gray-200 p-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        @foreach ($questionPaper->images as $image)
                            <div class="bg-gray-50 p-2 rounded">
                                <img src="{{ Storage::url($image->file_path) }}" alt="{{ $image->caption }}" class="w-full h-auto rounded">
                                <p class="mt-1 text-xs text-gray-500">{{ $image->caption }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
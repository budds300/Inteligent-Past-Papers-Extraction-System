<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class QuestionPaper extends Model
{
    use HasFactory;

    protected $fillable = [
        'examiner_id',
        'subject_id',
        'class_id',
        'curriculum_id',
        'paper_type_id',
        'term_id',
        'year',
        'title',
        'original_file_path',
        'original_file_name',
        'original_file_type',
        'processing_complete',
        'processing_error',
    ];

    public function examiner(): BelongsTo
    {
        return $this->belongsTo(Examiner::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classLevel(): BelongsTo
    {
        return $this->belongsTo(ClassLevel::class, 'class_id');
    }

    public function curriculum(): BelongsTo
    {
        return $this->belongsTo(Curriculum::class);
    }

    public function paperType(): BelongsTo
    {
        return $this->belongsTo(PaperType::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class)->whereNull('parent_id');
    }

    public function allQuestions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Image::class, 'imageable');
    }
}
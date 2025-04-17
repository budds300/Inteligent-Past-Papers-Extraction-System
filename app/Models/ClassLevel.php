<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClassLevel extends Model
{
    use HasFactory;
    
    protected $table = 'classes'; // Since 'Class' is a reserved word in PHP

    protected $fillable = [
        'name',
        'level',
        'description',
    ];

    public function questionPapers(): HasMany
    {
        return $this->hasMany(QuestionPaper::class, 'class_id');
    }
}
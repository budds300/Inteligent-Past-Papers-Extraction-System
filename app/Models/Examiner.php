<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examiner extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'institution',
    ];

    public function questionPapers(): HasMany
    {
        return $this->hasMany(QuestionPaper::class);
    }
}

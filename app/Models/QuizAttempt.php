<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'student_id',
        'score',
        'detail',
        'started_at',   
        'completed_at',
        'graded_at',    

    ];

    protected $casts = [
        'detail'       => 'array',
        'started_at'   => 'datetime',  
        'completed_at' => 'datetime',
        'graded_at'    => 'datetime',  
        // optional but nice if score is marks-based:
        // 'score'      => 'decimal:2',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function attemptAnswers()
    {
        return $this->hasMany(\App\Models\AttemptAnswer::class, 'attempt_id');
    }

}

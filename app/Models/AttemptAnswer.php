<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttemptAnswer extends Model
{
    protected $fillable = [
        'attempt_id','question_id','answer_id','response_text',
        'is_correct','awarded_marks','marking_method'
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'awarded_marks' => 'decimal:2',
    ];

    public function attempt()  { return $this->belongsTo(QuizAttempt::class, 'attempt_id'); }
    public function question() { return $this->belongsTo(Question::class); }
    public function answer()   { return $this->belongsTo(Answer::class); }
}

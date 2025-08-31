<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Question extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'text',
        'type',             // 'mcq' | 'tf' | 'short'
        'correct_answer',   // nullable string (legacy mirror)
        'scoring',          // 'exact' | 'partial' | 'negative' | 'manual'
        'marks',
        'penalty',
        'data',             // array: ['prompt'=>..., 'options'=>[...]]
        'answer',           // array: canonical answer(s)
    ];

    protected $casts = [
        'data'    => 'array',
        'answer'  => 'array',
        'marks'   => 'float',
        'penalty' => 'float',
    ];

    public function quiz()
    {
        return $this->belongsTo(Quiz::class);
    }

    public function answers()
    {
        return $this->hasMany(Answer::class, 'question_id');
    }


    // Keep ONLY this so text mirrors data.prompt. Let casts handle JSON.
    public function setTextAttribute($value): void
    {
        $this->attributes['text'] = $value;
        $data = $this->data ?? [];
        $data['prompt'] = $value;
        $this->attributes['data'] = $data;  // array; cast will serialize
    }
}

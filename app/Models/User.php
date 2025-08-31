<?php
// Author: Chong Pei Lee
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Laravel\Sanctum\HasApiTokens; 

class User extends Authenticatable implements AuthenticatableContract
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'name','email','password','role'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
    
    public function createdQuizzes() {
        return $this->hasMany(Quiz::class, 'teacher_id');
    }

    public function quizAttempts() {
        return $this->hasMany(QuizAttempt::class, 'student_id');
    }
}

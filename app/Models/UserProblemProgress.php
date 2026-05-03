<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserProblemProgress extends Model
{
    protected $table = 'user_problem_progress';

    protected $fillable = [
        'user_id',
        'problem_id',
        'completed',
        'score',
        'completed_at',
    ];
}

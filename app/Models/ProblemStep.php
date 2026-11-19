<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProblemStep extends Model
{
    protected $fillable = [
        'problem_id',
        'step_number',
        'title',
        'instruction',
        'answer_type',
        'allows_multiple_correct',
        'correct_answer',
        'tolerance',
        'tolerance_type',
        'unit',
        'success_message',
        'error_message',
        'reminder_message',
        'image_url',
        'image_alt',
        'image_caption',
        'image_source',
        'image_align',
        'image_max_width',
        'image_trigger',
    ];

    protected $casts = [
        'tolerance' => 'float',
        'step_number' => 'integer',
        'allows_multiple_correct' => 'boolean',
    ];

    public function problem()
    {
        return $this->belongsTo(Problem::class);
    }

    public function options()
    {
        return $this->hasMany(ProblemStepOption::class)->orderBy('order');
    }
}

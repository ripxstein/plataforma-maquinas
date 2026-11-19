<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProblemStepOption extends Model
{
    protected $fillable = [
        'problem_step_id',
        'option_text',
        'image_url',
        'image_alt',
        'is_correct',
        'order',
    ];

    protected $casts = [
        'is_correct' => 'boolean',
        'order' => 'integer',
    ];

    public function step()
    {
        return $this->belongsTo(ProblemStep::class, 'problem_step_id');
    }
}

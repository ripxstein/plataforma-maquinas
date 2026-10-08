<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Problem extends Model
{
    protected $fillable = [
        'module_item_id',
        'title',
        'slug',
        'component',
        'content',
        'order',
        'percentage',
        'is_active',
        'is_example',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_example' => 'boolean',
    ];

    public function scopeExamples($query)
    {
        return $query->where('is_example', true);
    }

    public function scopeExercises($query)
    {
        return $query->where('is_example', false);
    }

    public function moduleItem()
    {
        return $this->belongsTo(ModuleItem::class);
    }

    public function steps()
    {
        return $this->hasMany(ProblemStep::class)->orderBy('step_number');
    }
}

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
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function moduleItem()
    {
        return $this->belongsTo(ModuleItem::class);
    }

    public function steps()
    {
        return $this->hasMany(ProblemStep::class)->orderBy('step_number');
    }
}

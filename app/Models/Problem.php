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
        'content', // 👈 agregar
        'order',
        'percentage',
    ];

    public function moduleItem()
    {
        return $this->belongsTo(ModuleItem::class);
    }
}

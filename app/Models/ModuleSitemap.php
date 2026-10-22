<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleSitemap extends Model
{
    protected $fillable = [
        'module_id',
        'nodes',
        'edges',
        'is_active',
    ];

    protected $casts = [
        'nodes' => 'array',
        'edges' => 'array',
        'is_active' => 'boolean',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleItem extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'type',
        'component',
        'content',
        'order',
        'percentage',
    ];

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function problems()
    {
        return $this->hasMany(Problem::class)->orderBy('order');
    }

    public function progress()
    {
        return $this->hasMany(UserItemProgress::class);
    }
}

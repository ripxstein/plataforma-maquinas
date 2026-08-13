<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'order',
    ];

    public function items()
    {
        return $this->hasMany(ModuleItem::class)->orderBy('order');
    }
}


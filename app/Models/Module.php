<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    public function items()
    {
        return $this->hasMany(ModuleItem::class)->orderBy('order');
    }
}

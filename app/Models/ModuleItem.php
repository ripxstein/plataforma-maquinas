<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModuleItem extends Model
{
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function progress()
    {
        return $this->hasMany(UserItemProgress::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserItemProgress extends Model
{
    protected $fillable = [
        'user_id',
        'module_item_id',
        'completed',
        'completed_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function moduleItem()
    {
        return $this->belongsTo(ModuleItem::class);
    }
}

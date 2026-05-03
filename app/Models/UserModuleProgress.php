<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserModuleProgress extends Model
{
    protected $table = 'user_module_progress';

    protected $fillable = [
        'user_id',
        'module_id',
        'unlocked_order',
    ];
}

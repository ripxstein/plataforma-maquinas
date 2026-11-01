<?php

namespace App\Models;

use App\Services\UniqueSlugService;
use Illuminate\Database\Eloquent\Model;

class ModuleItem extends Model
{
    protected $fillable = [
        'module_id',
        'title',
        'slug',
        'type',
        'component',
        'content',
        'order',
        'percentage',
    ];

    protected static function booted(): void
    {
        static::saving(function (ModuleItem $item) {
            if (! $item->slug || ($item->isDirty('title') && ! $item->isDirty('slug'))) {
                $item->slug = app(UniqueSlugService::class)->generate(
                    self::class,
                    $item->title,
                    $item->exists ? (int) $item->id : null,
                );
            }
        });
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function problems()
    {
        return $this->hasMany(Problem::class)->orderBy('order')->orderBy('id');
    }

    public function activeProblems()
    {
        return $this->hasMany(Problem::class)->where('is_active', true)->orderBy('order')->orderBy('id');
    }

    public function progress()
    {
        return $this->hasMany(UserItemProgress::class);
    }
}

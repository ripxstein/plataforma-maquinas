<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UniqueSlugService
{
    /**
     * @param  class-string<Model>  $modelClass
     */
    public function generate(string $modelClass, string $source, ?int $ignoreId = null): string
    {
        $base = Str::slug($source);
        $base = $base !== '' ? $base : 'sin-titulo';
        $candidate = Str::limit($base, 255, '');
        $suffix = 2;

        while ($modelClass::query()
            ->where('slug', $candidate)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $ending = '-'.$suffix++;
            $candidate = Str::limit($base, 255 - strlen($ending), '').$ending;
        }

        return $candidate;
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_items', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
        });

        $used = [];

        DB::table('module_items')
            ->select('id', 'title')
            ->orderBy('id')
            ->chunkById(200, function ($items) use (&$used) {
                foreach ($items as $item) {
                    $base = Str::slug($item->title) ?: 'lectura';
                    $candidate = Str::limit($base, 255, '');
                    $suffix = 2;

                    while (isset($used[$candidate])) {
                        $ending = '-'.$suffix++;
                        $candidate = Str::limit($base, 255 - strlen($ending), '').$ending;
                    }

                    $used[$candidate] = true;
                    DB::table('module_items')->where('id', $item->id)->update(['slug' => $candidate]);
                }
            });

        Schema::table('module_items', function (Blueprint $table) {
            $table->unique('slug', 'module_items_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::table('module_items', function (Blueprint $table) {
            $table->dropUnique('module_items_slug_unique');
            $table->dropColumn('slug');
        });
    }
};

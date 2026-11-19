<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('problem_steps', function (Blueprint $table) {
            $table->boolean('allows_multiple_correct')->default(false);
        });
        Schema::table('problem_step_options', function (Blueprint $table) {
            $table->text('image_url')->nullable();
            $table->string('image_alt')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('problem_step_options', fn (Blueprint $table) => $table->dropColumn(['image_url', 'image_alt']));
        Schema::table('problem_steps', fn (Blueprint $table) => $table->dropColumn('allows_multiple_correct'));
    }
};

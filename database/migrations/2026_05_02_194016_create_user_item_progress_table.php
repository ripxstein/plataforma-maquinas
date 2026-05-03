<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_item_progress', function (Blueprint $table) {
    $table->id();

    $table->foreignId('user_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->foreignId('module_item_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->boolean('completed')->default(false);
    $table->timestamp('completed_at')->nullable();

    $table->timestamps();

    $table->unique(['user_id', 'module_item_id']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_item_progress');
    }
};

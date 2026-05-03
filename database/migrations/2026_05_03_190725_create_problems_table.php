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
        Schema::create('problems', function (Blueprint $table) {
            $table->id();

            $table->foreignId('module_item_id')
                ->constrained('module_items')
                ->cascadeOnDelete();

            $table->string('title');
            $table->string('slug')->unique();
            $table->string('component');
            $table->integer('order')->default(1);
            $table->unsignedTinyInteger('percentage')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('problems');
    }
};

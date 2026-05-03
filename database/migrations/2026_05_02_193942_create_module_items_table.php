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
        Schema::create('module_items', function (Blueprint $table) {
    $table->id();

    $table->foreignId('module_id')
        ->constrained()
        ->cascadeOnDelete();

    $table->string('title');
    $table->string('type'); 
    // lectura o problema

    $table->string('component')->nullable();
    // ejemplo: problemas.problema1

    $table->text('content')->nullable();
    // para lecturas simples

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
        Schema::dropIfExists('module_items');
    }
};

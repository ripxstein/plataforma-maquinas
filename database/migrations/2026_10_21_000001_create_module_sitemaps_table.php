<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mapa conceptual (sitemap) por módulo: nodos y conexiones en JSON.
     */
    public function up(): void
    {
        Schema::create('module_sitemaps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->unique()->constrained('modules')->cascadeOnDelete();
            $table->json('nodes')->nullable();
            $table->json('edges')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('module_sitemaps');
    }
};

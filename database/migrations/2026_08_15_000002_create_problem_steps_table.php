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
        Schema::create('problem_steps', function (Blueprint $table) {
            $table->id();

            $table->foreignId('problem_id')
                ->constrained('problems')
                ->cascadeOnDelete();

            $table->unsignedInteger('step_number')->default(1);
            $table->string('title');
            $table->longText('instruction')->nullable();
            
            // Response type: numeric, multiple_choice, true_false, text
            $table->string('answer_type')->default('numeric');
            
            // Expected answer and tolerances
            $table->text('correct_answer')->nullable();
            $table->double('tolerance')->nullable()->default(0.01);
            $table->string('tolerance_type')->default('absolute'); // absolute, percentage
            $table->string('unit')->nullable();

            // Feedback messages
            $table->text('success_message')->nullable();
            $table->text('error_message')->nullable();
            $table->text('reminder_message')->nullable();

            // Illustration / Graphic integration
            $table->string('image_url')->nullable();
            $table->string('image_alt')->nullable();
            $table->string('image_caption')->nullable();
            $table->string('image_source')->nullable();
            $table->string('image_align')->default('align-center'); // align-center, align-left, align-right
            $table->string('image_max_width')->default('100%');
            $table->string('image_trigger')->default('always'); // always, on_error, on_success

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('problem_steps');
    }
};

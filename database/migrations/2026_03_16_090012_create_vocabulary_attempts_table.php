<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocabulary_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vocabulary_task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('total_words')->default(0);
            $table->unsignedInteger('correct_words')->default(0);
            $table->decimal('score_percent', 5, 2)->default(0);
            $table->boolean('is_passed')->default(false);
            $table->unsignedInteger('attempt_number')->default(1);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_attempts');
    }
};

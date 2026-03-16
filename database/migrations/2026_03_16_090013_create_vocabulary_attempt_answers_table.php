<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocabulary_attempt_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vocabulary_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vocabulary_word_id')->constrained()->cascadeOnDelete();
            $table->string('student_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('time_taken_seconds')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_attempt_answers');
    }
};

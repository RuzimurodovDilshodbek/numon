<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['pending', 'submitted', 'graded', 'missed'])->default('pending');
            $table->string('file_path')->nullable();
            $table->text('text_content')->nullable();
            $table->unsignedInteger('score')->nullable();
            $table->text('teacher_comment')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
            $table->unique(['task_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_submissions');
    }
};

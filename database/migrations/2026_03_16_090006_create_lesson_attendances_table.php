<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['present', 'absent', 'late', 'excused'])->default('absent');
            $table->timestamp('checked_in_at')->nullable();
            $table->enum('check_in_method', ['secret_code', 'manual'])->nullable();
            $table->timestamps();
            $table->unique(['lesson_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_attendances');
    }
};

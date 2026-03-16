<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bot_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('telegram_id')->unique();
            $table->foreignId('student_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('step', ['started', 'id_entered', 'questionnaire', 'photo', 'completed'])->default('started');
            $table->jsonb('temp_data')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bot_registrations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocabulary_words', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vocabulary_list_id')->constrained()->cascadeOnDelete();
            $table->string('turkish_word');
            $table->string('uzbek_translation');
            $table->text('example_sentence')->nullable();
            $table->unsignedTinyInteger('difficulty_level')->default(1);
            $table->string('audio_path')->nullable();
            $table->unsignedInteger('order_index')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_words');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vocabulary_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vocabulary_list_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('pass_percent')->default(80);
            $table->unsignedInteger('time_limit_minutes')->nullable();
            $table->boolean('random_order')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vocabulary_tasks');
    }
};

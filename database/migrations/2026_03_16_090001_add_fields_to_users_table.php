<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('student_id', 6)->unique()->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('telegram_id')->unique()->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('telegram_photo_url')->nullable();
            $table->string('photo_path')->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'student_id', 'phone', 'telegram_id',
                'telegram_username', 'telegram_photo_url',
                'photo_path', 'is_active',
            ]);
        });
    }
};

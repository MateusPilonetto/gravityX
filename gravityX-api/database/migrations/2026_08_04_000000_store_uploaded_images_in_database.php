<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Store uploaded media with the record instead of in the web-service
     * filesystem. PostgreSQL maps binary columns to bytea.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->binary('profile_photo_data')->nullable();
            $table->string('profile_photo_mime_type', 100)->nullable();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->binary('image_data')->nullable();
            $table->string('image_mime_type', 100)->nullable();
        });
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->dropColumn(['image_data', 'image_mime_type']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['profile_photo_data', 'profile_photo_mime_type']);
        });
    }
};

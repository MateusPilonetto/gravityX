<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['user_id', 'created_at'], 'posts_user_created_at_index');
            $table->index('created_at', 'posts_created_at_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['post_id', 'created_at'], 'comments_post_created_at_index');
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->index('post_id', 'likes_post_id_index');
        });

        Schema::table('stories', function (Blueprint $table) {
            $table->index(['user_id', 'expires_at'], 'stories_user_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('stories', function (Blueprint $table) {
            $table->dropIndex('stories_user_expires_at_index');
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->dropIndex('likes_post_id_index');
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->dropIndex('comments_post_created_at_index');
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->dropIndex('posts_created_at_index');
            $table->dropIndex('posts_user_created_at_index');
        });
    }
};

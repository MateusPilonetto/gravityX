<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add the field in a separate migration because the stories table may
     * already exist in deployed databases.
     */
    public function up(): void
    {
        if (Schema::hasColumn('stories', 'media_type')) {
            return;
        }

        Schema::table('stories', function (Blueprint $table) {
            $table->enum('media_type', ['image', 'video'])
                ->default('image');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('stories', 'media_type')) {
            return;
        }

        Schema::table('stories', function (Blueprint $table) {
            $table->dropColumn('media_type');
        });
    }
};

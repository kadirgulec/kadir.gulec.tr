<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('watchables', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('slug');
            $table->unsignedInteger('tmdb_id')->nullable();
            $table->string('title');
            $table->string('original_title')->nullable();
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('creator')->nullable();
            $table->json('genres')->nullable();
            $table->unsignedSmallInteger('runtime_minutes')->nullable();
            $table->text('overview')->nullable();
            $table->json('cast')->nullable();
            $table->string('poster_path')->nullable();
            $table->string('accent', 7)->nullable();
            $table->json('poster_colors')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->longText('review')->nullable();
            $table->longText('review_html')->nullable();
            $table->timestamp('review_published_at')->nullable();
            $table->string('series_status')->nullable();
            $table->unsignedSmallInteger('current_season')->nullable();
            $table->unsignedSmallInteger('current_episode')->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['type', 'slug']);
            $table->unique(['type', 'tmdb_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('watchables');
    }
};

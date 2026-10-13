<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Books: their facts (often from Open Library, stored locally) and Kadir's
 * take on them (rating, favorite, review). A book with a reading list
 * position is on "okuyacaklarım"; its first reading takes it off again.
 * There is no page count: Kadir reads e-books, progress is a percentage.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('isbn', 20)->nullable()->index();
            $table->string('open_library_key', 40)->nullable()->index();
            $table->string('title');
            $table->string('original_title')->nullable();
            $table->string('author');
            $table->string('translator')->nullable();
            $table->string('publisher')->nullable();
            $table->smallInteger('year')->nullable();
            $table->string('language', 40)->default('Türkçe');
            $table->string('format')->default('ebook');
            $table->text('overview')->nullable();
            $table->string('cover_path')->nullable();
            $table->string('accent', 7)->nullable();
            $table->json('cover_colors')->nullable();
            $table->decimal('rating', 3, 1)->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->longText('review')->nullable();
            $table->longText('review_html')->nullable();
            $table->timestamp('review_published_at')->nullable();
            $table->unsignedInteger('reading_list_position')->nullable()->index();
            $table->string('reading_list_note', 160)->nullable();
            $table->string('meta_description')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * When a book's followers were told about its review (scheduled ones included).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('books', function (Blueprint $table) {
            $table->timestamp('review_announced_at')->nullable()->after('review_published_at');
        });

        // What is already public counts as announced, so nobody gets old news.
        DB::table('books')->whereNotNull('review_published_at')->where('review_published_at', '<=', now())->update(['review_announced_at' => DB::raw('review_published_at')]);
    }

    public function down(): void
    {
        Schema::table('books', fn (Blueprint $table) => $table->dropColumn('review_announced_at'));
    }
};

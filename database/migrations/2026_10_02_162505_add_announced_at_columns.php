<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // When followers were told about a publication (scheduled ones included).
        Schema::table('posts', function (Blueprint $table) {
            $table->timestamp('announced_at')->nullable()->after('published_at');
        });

        Schema::table('watchables', function (Blueprint $table) {
            $table->timestamp('review_announced_at')->nullable()->after('review_published_at');
        });

        // What is already public counts as announced, so nobody gets old news.
        DB::table('posts')->whereNotNull('published_at')->where('published_at', '<=', now())->update(['announced_at' => DB::raw('published_at')]);
        DB::table('watchables')->whereNotNull('review_published_at')->where('review_published_at', '<=', now())->update(['review_announced_at' => DB::raw('review_published_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropColumn('announced_at'));
        Schema::table('watchables', fn (Blueprint $table) => $table->dropColumn('review_announced_at'));
    }
};

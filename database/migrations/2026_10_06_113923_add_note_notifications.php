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
        // When subscribers were told about a note (scheduled ones included).
        Schema::table('notes', function (Blueprint $table) {
            $table->timestamp('announced_at')->nullable()->after('published_at');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_new_notes')->default(false)->after('notify_new_posts');
        });

        // Items that wait for the daily or weekly digest even when the member wants e-mails at once.
        Schema::table('notification_items', function (Blueprint $table) {
            $table->boolean('digest_only')->default(false)->after('url');
        });

        // What is already public counts as announced, so nobody gets old news.
        DB::table('notes')->whereNotNull('published_at')->where('published_at', '<=', now())->update(['announced_at' => DB::raw('published_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', fn (Blueprint $table) => $table->dropColumn('announced_at'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('notify_new_notes'));
        Schema::table('notification_items', fn (Blueprint $table) => $table->dropColumn('digest_only'));
    }
};

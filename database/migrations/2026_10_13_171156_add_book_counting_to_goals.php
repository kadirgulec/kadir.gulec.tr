<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Okuduklarım'dan sayılsın": a numeric yearly goal with the switch on gets
 * one progress entry per book finished in its year, linked to the reading,
 * so the entry follows the reading (moved, or gone when it is undone).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->boolean('counts_books')->default(false)->after('show_progress_notes');
        });

        Schema::table('goal_progress', function (Blueprint $table) {
            $table->foreignId('reading_id')->nullable()->unique()->after('goal_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('goal_progress', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reading_id');
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->dropColumn('counts_books');
        });
    }
};

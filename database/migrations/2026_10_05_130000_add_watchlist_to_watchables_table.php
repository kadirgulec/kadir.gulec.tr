<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The watchlist ("izleyeceğim"): a film or series with a position is on it.
 * The first viewing takes it off again.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watchables', function (Blueprint $table) {
            $table->unsignedInteger('watchlist_position')->nullable()->after('current_episode')->index();
            $table->string('watchlist_note', 160)->nullable()->after('watchlist_position');
        });
    }

    public function down(): void
    {
        Schema::table('watchables', function (Blueprint $table) {
            $table->dropColumn(['watchlist_position', 'watchlist_note']);
        });
    }
};

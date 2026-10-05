<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A chain counts days, weeks or months; a weekly or monthly link holds when
 * chain_target days of it were marked. The reminders Kadir got are kept so
 * that the same one is not sent twice (the cache is cleared on every deploy).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('goals', function (Blueprint $table) {
            $table->string('chain_period')->default('day')->after('ended_on');
            $table->unsignedTinyInteger('chain_target')->default(1)->after('chain_period');
        });

        Schema::create('chain_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('goal_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->timestamps();

            $table->unique(['goal_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chain_reminders');

        Schema::table('goals', function (Blueprint $table) {
            $table->dropColumn(['chain_period', 'chain_target']);
        });
    }
};

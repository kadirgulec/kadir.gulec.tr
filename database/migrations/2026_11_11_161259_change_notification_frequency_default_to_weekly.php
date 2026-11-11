<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * New members get the weekly digest instead of the daily one, so we send less e-mail.
     * Existing members keep the frequency they have.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('notification_frequency')->default('weekly')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('notification_frequency')->default('daily')->change();
        });
    }
};

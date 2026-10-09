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
        Schema::create('monthly_reviews', function (Blueprint $table) {
            $table->id();
            $table->date('month')->unique();
            $table->string('summary')->nullable();
            $table->unsignedTinyInteger('score')->nullable();
            // The month's numbers, frozen when the review is made (see MonthlyReviewStats).
            $table->json('stats');
            // Keys of the number tiles Kadir chose not to show.
            $table->json('hidden_stats')->nullable();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('announced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('monthly_reviews');
    }
};

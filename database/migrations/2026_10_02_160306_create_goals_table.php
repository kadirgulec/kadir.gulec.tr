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
        Schema::create('goals', function (Blueprint $table) {
            $table->id();
            $table->string('kind');
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('visibility')->default('hidden');
            $table->foreignId('parent_id')->nullable()->constrained('goals')->nullOnDelete();
            $table->unsignedInteger('sort_order')->default(0);

            // Yearly goals
            $table->unsignedSmallInteger('year')->nullable()->index();
            $table->string('measure')->nullable();
            $table->unsignedInteger('target')->nullable();
            $table->string('unit', 40)->nullable();
            $table->timestamp('achieved_at')->nullable();
            $table->boolean('show_progress_notes')->default(false);

            // Long-term goals
            $table->text('why')->nullable();
            $table->text('why_html')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('started_year')->nullable();

            // Chains
            $table->date('started_on')->nullable();
            $table->date('ended_on')->nullable();

            $table->timestamps();

            $table->index(['kind', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goals');
    }
};

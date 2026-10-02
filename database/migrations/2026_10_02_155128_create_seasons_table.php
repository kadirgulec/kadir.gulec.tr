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
        Schema::create('seasons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('watchable_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            $table->unsignedSmallInteger('episode_count')->default(0);
            $table->decimal('rating', 3, 1)->nullable();
            $table->string('note')->nullable();
            $table->timestamps();

            $table->unique(['watchable_id', 'number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seasons');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every time a book was read, from start to finish (or to where it was left).
 * A reread is not stored; it is any reading with an older one before it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained()->cascadeOnDelete();
            $table->string('status')->index();
            $table->date('started_on')->index();
            $table->date('finished_on')->nullable()->index();
            $table->unsignedTinyInteger('progress')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('readings');
    }
};

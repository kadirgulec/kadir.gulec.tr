<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sections Kadir closed from the admin panel; a row means closed (see App\Models\ClosedSection).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('closed_sections', function (Blueprint $table) {
            $table->string('section', 20)->primary();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('closed_sections');
    }
};

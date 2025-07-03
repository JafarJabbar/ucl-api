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
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('strength_rating', 3, 2)->comment('must be between 0.00 and 1.00');
            $table->string('logo_url')->nullable();
            $table->string('short_name', 3)->comment('CHE, ARS, LIV, MCI');
            $table->bigInteger('external_id')->nullable()->comment('ID from external API');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};

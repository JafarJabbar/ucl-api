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
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_home_id')->constrained('teams');
            $table->foreignId('team_away_id')->constrained('teams');
            $table->integer('home_goals')->nullable();
            $table->integer('away_goals')->nullable();
            $table->integer('week');
            $table->tinyInteger('is_finished')->default(0)->comment('0-not yet played, 1-already played');
            $table->timestamp('played_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};

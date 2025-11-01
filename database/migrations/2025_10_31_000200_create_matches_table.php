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
        Schema::create('matches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignId('home_team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('away_team_id')->constrained('teams')->cascadeOnDelete();

            $table->unsignedTinyInteger('court_number')->nullable(); // 1-4, nullable until scheduled
            $table->dateTime('start_datetime')->nullable(); // null until scheduled
            $table->dateTime('end_datetime')->nullable(); // null until finished

            // Results
            $table->unsignedSmallInteger('home_goals')->nullable();
            $table->unsignedSmallInteger('away_goals')->nullable();
            $table->boolean('is_final')->default(false);
            $table->unsignedTinyInteger('unfinalize_count')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['tournament_id', 'start_datetime']);
            $table->index(['home_team_id']);
            $table->index(['away_team_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('matches');
    }
};

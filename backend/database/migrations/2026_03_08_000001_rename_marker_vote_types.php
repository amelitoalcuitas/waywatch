<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('marker_votes')
            ->where('vote_type', 'like')
            ->update(['vote_type' => 'still_there']);

        DB::table('marker_votes')
            ->where('vote_type', 'dislike')
            ->update(['vote_type' => 'not_there']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('marker_votes')
            ->where('vote_type', 'still_there')
            ->update(['vote_type' => 'like']);

        DB::table('marker_votes')
            ->where('vote_type', 'not_there')
            ->update(['vote_type' => 'dislike']);
    }
};

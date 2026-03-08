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
        Schema::table('markers', function (Blueprint $table) {
            $table->timestamp('base_expires_at')->nullable()->after('expires_at');
            $table->timestamp('max_expires_at')->nullable()->after('base_expires_at');
            $table->json('policy_snapshot')->nullable()->after('max_expires_at');

            $table->index('expires_at');
            $table->index('base_expires_at');
            $table->index('max_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('markers', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropIndex(['base_expires_at']);
            $table->dropIndex(['max_expires_at']);

            $table->dropColumn(['base_expires_at', 'max_expires_at', 'policy_snapshot']);
        });
    }
};

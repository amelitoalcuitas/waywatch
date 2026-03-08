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
        Schema::create('marker_lifetime_policies', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable();
            $table->unsignedInteger('base_lifetime_minutes');
            $table->unsignedInteger('min_lifetime_minutes');
            $table->unsignedInteger('max_lifetime_minutes');
            $table->unsignedInteger('still_there_extension_minutes')->default(30);
            $table->unsignedInteger('not_there_reduction_minutes')->default(45);
            $table->unsignedInteger('grace_period_minutes')->default(30);
            $table->unsignedInteger('early_expiry_quorum')->default(5);
            $table->decimal('early_expiry_not_there_ratio', 5, 4)->default(0.8);
            $table->unsignedInteger('early_expiry_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marker_lifetime_policies');
    }
};

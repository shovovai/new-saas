<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('price_monthly')->default(0)->comment('minor units, e.g. cents');
            $table->unsignedInteger('price_yearly')->default(0)->comment('minor units, e.g. cents');
            $table->unsignedInteger('max_websites')->default(1);
            $table->unsignedInteger('max_team_members')->default(1);
            $table->unsignedInteger('max_scans_per_month')->default(10);
            $table->string('scan_frequency')->default('daily')->comment('5min|15min|30min|1hr|6hr|daily');
            $table->unsignedInteger('ai_credits')->default(0);
            $table->unsignedInteger('storage_mb')->default(100);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};

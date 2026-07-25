<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accessibility_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_job_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('score')->nullable();
            $table->unsignedInteger('violations_count')->default(0);
            $table->json('findings')->nullable()->comment('[{category, severity, title, explanation, recommendation}]');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accessibility_reports');
    }
};

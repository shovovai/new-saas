<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_job_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('score')->nullable()->comment('Lighthouse-style 0-100');
            $table->unsignedInteger('lcp_ms')->nullable();
            $table->decimal('cls', 5, 3)->nullable();
            $table->unsignedInteger('inp_ms')->nullable();
            $table->unsignedInteger('ttfb_ms')->nullable();
            $table->json('findings')->nullable()->comment('[{category, severity, title, explanation, recommendation}]');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reports');
    }
};

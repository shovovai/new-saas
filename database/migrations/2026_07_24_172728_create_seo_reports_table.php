<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seo_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_job_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('score')->nullable();
            $table->unsignedInteger('broken_links_count')->default(0);
            $table->unsignedInteger('missing_alt_count')->default(0);
            $table->boolean('has_sitemap')->nullable();
            $table->boolean('has_robots_txt')->nullable();
            $table->json('findings')->nullable()->comment('[{category, severity, title, explanation, recommendation}]');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_reports');
    }
};

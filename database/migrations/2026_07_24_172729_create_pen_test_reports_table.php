<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pen_test_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pen_test_authorization_id')->constrained();
            $table->foreignId('monitoring_job_id')->nullable()->constrained()->nullOnDelete();
            $table->json('categories_tested');
            $table->decimal('cvss_score', 3, 1)->nullable();
            $table->string('risk_level')->nullable()->comment('low|medium|high|critical');
            $table->json('findings')->nullable()->comment('[{category, severity, owasp_mapping, cvss_score, explanation, recommendation}]');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pen_test_reports');
    }
};

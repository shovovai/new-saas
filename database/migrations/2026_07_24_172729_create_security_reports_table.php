<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('security_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('monitoring_job_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('score')->nullable();
            $table->boolean('ssl_valid')->nullable();
            $table->timestamp('ssl_expires_at')->nullable();
            $table->boolean('has_sensitive_file_exposure')->default(false);
            $table->json('missing_headers')->nullable();
            $table->json('findings')->nullable()->comment('[{category, severity, title, explanation, recommendation}]');
            $table->json('raw')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('security_reports');
    }
};

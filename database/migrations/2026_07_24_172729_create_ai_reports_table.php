<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Output of an AI analysis pass over SEO/performance/accessibility/
     * security/content/etc. (Functional Spec §7): summary, recommendations,
     * risk level, fix instructions, estimated impact, priority.
     */
    public function up(): void
    {
        Schema::create('ai_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('type')->comment('summary|fix_generation|analysis');
            $table->string('source_report_type')->nullable()->comment('performance|seo|security|accessibility');
            $table->unsignedBigInteger('source_report_id')->nullable();
            $table->text('summary')->nullable();
            $table->json('recommendations')->nullable();
            $table->string('risk_level')->nullable()->comment('low|medium|high|critical');
            $table->string('estimated_impact')->nullable();
            $table->string('priority')->nullable()->comment('low|medium|high');
            $table->string('model_used')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_reports');
    }
};

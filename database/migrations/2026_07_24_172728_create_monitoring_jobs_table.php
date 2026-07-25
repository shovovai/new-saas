<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per dispatched scan/monitoring run (performance, seo,
     * security, accessibility, pen_test, uptime) — queued via Horizon,
     * never run synchronously in a request (Architecture §7).
     */
    public function up(): void
    {
        Schema::create('monitoring_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dispatched_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->comment('uptime|performance|seo|security|accessibility|pen_test');
            $table->string('status')->default('queued')->comment('queued|running|completed|failed');
            $table->text('failure_reason')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_jobs');
    }
};

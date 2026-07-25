<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Continuous check results: online/offline, response time, SSL expiry,
     * domain expiry, DNS changes, redirect changes, homepage availability
     * (Functional Spec §6).
     */
    public function up(): void
    {
        Schema::create('monitoring_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->string('check_type')->comment('uptime|response_time|ssl_expiry|domain_expiry|dns_change|redirect_change');
            $table->string('status')->comment('ok|warning|critical');
            $table->decimal('metric_value', 12, 2)->nullable()->comment('e.g. ms response time, days to expiry');
            $table->json('details')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'check_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monitoring_logs');
    }
};

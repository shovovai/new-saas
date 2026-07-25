<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-team/user alert-type notification preferences (Functional Spec
     * §11): which channels fire for which alert type. Actual sent
     * notifications land in the standard `notifications` table via
     * Laravel's Notifiable/Notification system.
     */
    public function up(): void
    {
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('alert_type')->comment('site_down|ssl_expiring|domain_expiring|scan_failed|scan_completed|pentest_completed');
            $table->json('channels')->comment('["email","sms","slack","discord","telegram","push","webhook"]');
            $table->string('webhook_url')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->unique(['team_id', 'user_id', 'alert_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};

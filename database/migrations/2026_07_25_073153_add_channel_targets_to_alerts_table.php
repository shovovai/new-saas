<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-channel destinations. `webhook_url` (already on the table) is
     * used for the generic "webhook" channel; these add the destination
     * each of the other real channels actually needs.
     */
    public function up(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->string('slack_webhook_url')->nullable()->after('webhook_url');
            $table->string('discord_webhook_url')->nullable()->after('slack_webhook_url');
            $table->string('telegram_chat_id')->nullable()->after('discord_webhook_url');
            $table->string('phone_number')->nullable()->after('telegram_chat_id');
        });
    }

    public function down(): void
    {
        Schema::table('alerts', function (Blueprint $table) {
            $table->dropColumn(['slack_webhook_url', 'discord_webhook_url', 'telegram_chat_id', 'phone_number']);
        });
    }
};

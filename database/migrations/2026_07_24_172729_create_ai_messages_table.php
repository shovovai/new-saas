<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Chat history for the per-website AI Assistant (UIUX §5) — scoped to
     * a website so the sidebar can show "this site's" conversation with
     * its scan history as visible, referenceable context.
     */
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->comment('user|assistant');
            $table->text('content');
            $table->json('context')->nullable()->comment('referenced report ids/findings for this turn');
            $table->timestamps();

            $table->index(['website_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};

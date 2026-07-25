<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The second, explicit authorization step for pen testing beyond basic
     * website verification (Architecture §6) — a legal/liability boundary,
     * not just UX. PenTestService must check both website.status ===
     * verified AND an active row here covering the requested categories.
     */
    public function up(): void
    {
        Schema::create('pen_test_authorizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('website_id')->constrained()->cascadeOnDelete();
            $table->foreignId('authorized_by_user_id')->constrained('users');
            $table->json('scope')->comment('which test categories are authorized, e.g. ["sqli","xss","csrf"]');
            $table->timestamp('authorized_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['website_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pen_test_authorizations');
    }
};

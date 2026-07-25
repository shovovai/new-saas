<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Works for an email that doesn't have an account yet — accepting
     * either logs the existing user in and attaches them, or carries the
     * invitation through registration (see RegisteredUserController).
     */
    public function up(): void
    {
        Schema::create('team_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by_user_id')->constrained('users');
            $table->string('email');
            $table->string('role');
            $table->string('token')->unique();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['team_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_invitations');
    }
};

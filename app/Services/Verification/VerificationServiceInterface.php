<?php

namespace App\Services\Verification;

use App\Models\Website;
use App\Models\WebsiteVerification;

interface VerificationServiceInterface
{
    public function method(): string;

    /**
     * Create (or return the existing) WebsiteVerification row with a fresh
     * random token for this method, without changing the website's status.
     */
    public function provision(Website $website): WebsiteVerification;

    /**
     * Attempt to verify the site against this method. Never flips
     * Website.status itself — that is the sole responsibility of
     * WebsiteVerificationManager.
     */
    public function verify(WebsiteVerification $verification): bool;

    /**
     * Human-readable on-screen instructions (record/file/tag content,
     * estimated setup time) for the "Add Website" verification panel.
     *
     * @return array{summary: string, instructions: string, estimated_minutes: int}
     */
    public function instructions(WebsiteVerification $verification): array;
}

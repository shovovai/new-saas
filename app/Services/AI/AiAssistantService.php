<?php

namespace App\Services\AI;

use App\Models\AiMessage;
use App\Models\User;
use App\Models\Website;
use Illuminate\Support\Collection;

/**
 * Per-website AI Assistant (Functional Spec §7 / UIUX §5): chat scoped to
 * one website, with that site's recent scan history as visible context.
 * Every call site MUST have already passed EnsureWebsiteVerified +
 * FeatureGateService::can($website, 'ai.assistant') — this service itself
 * does not re-check those gates, it assumes the caller (controller or job)
 * already did, exactly like the other scan services.
 */
class AiAssistantService
{
    public function __construct(private readonly AiProviderInterface $provider) {}

    public function ask(Website $website, User $user, string $question): AiMessage
    {
        AiMessage::create([
            'website_id' => $website->id,
            'user_id' => $user->id,
            'role' => 'user',
            'content' => $question,
        ]);

        $history = $this->recentHistory($website);

        $messages = $history
            ->map(fn (AiMessage $m) => ['role' => $m->role === 'assistant' ? 'assistant' : 'user', 'content' => $m->content])
            ->values()
            ->all();

        try {
            $answer = $this->provider->complete($messages, $this->systemPrompt($website));
        } catch (AiProviderException $e) {
            $answer = "I'm unable to reach the AI provider right now ({$e->getMessage()}). Please try again shortly, or check the AI provider configuration in Admin > AI Settings.";
        }

        return AiMessage::create([
            'website_id' => $website->id,
            'user_id' => $user->id,
            'role' => 'assistant',
            'content' => $answer,
        ]);
    }

    public function generateFix(Website $website, User $user, string $platform, string $issue): AiMessage
    {
        return $this->ask(
            $website,
            $user,
            "Generate a {$platform} configuration fix for the following issue on {$website->domain}: {$issue}",
        );
    }

    private function recentHistory(Website $website): Collection
    {
        return $website->aiMessages()->latest()->limit(20)->get()->reverse()->values();
    }

    private function systemPrompt(Website $website): string
    {
        $scores = $website->latestScores;

        return "You are SiteGuardian AI's assistant for the website {$website->domain}. ".
            'Current scores — performance: '.($scores['performance'] ?? 'n/a').
            ', SEO: '.($scores['seo'] ?? 'n/a').
            ', security: '.($scores['security'] ?? 'n/a').
            ', accessibility: '.($scores['accessibility'] ?? 'n/a').
            '. Answer questions about this site\'s monitoring, SEO, performance, accessibility, and security, '.
            'and produce concrete, minimal configuration fixes (nginx/Apache/Laravel/Cloudflare) when asked.';
    }
}

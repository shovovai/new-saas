<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

/**
 * Claude is the primary AI provider (Architecture §1). OpenAI/Gemini are
 * pluggable fallbacks implementing the same AiProviderInterface — swap the
 * DomainServiceProvider binding to change provider without touching
 * AiAssistantService or ReportSummarizerService.
 */
class AnthropicProvider implements AiProviderInterface
{
    public function name(): string
    {
        return 'anthropic';
    }

    public function complete(array $messages, ?string $systemPrompt = null): string
    {
        $apiKey = config('services.anthropic.key');

        if (! $apiKey) {
            throw new AiProviderException('Anthropic API key is not configured.');
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', array_filter([
            'model' => config('services.anthropic.model', 'claude-sonnet-5'),
            'max_tokens' => 1024,
            'system' => $systemPrompt,
            'messages' => $messages,
        ]));

        if (! $response->successful()) {
            throw new AiProviderException('Anthropic API request failed: '.$response->body());
        }

        return collect($response->json('content'))
            ->where('type', 'text')
            ->pluck('text')
            ->implode("\n");
    }
}

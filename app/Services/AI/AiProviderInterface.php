<?php

namespace App\Services\AI;

interface AiProviderInterface
{
    /**
     * @param  list<array{role: string, content: string}>  $messages
     */
    public function complete(array $messages, ?string $systemPrompt = null): string;

    public function name(): string;
}

<?php

namespace App\Services;

use App\Exceptions\AnthropicException;
use Generator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicService
{
    private ?string $apiKey;
    private string $chatModel;
    private string $summarizeModel;
    private string $baseUrl = 'https://api.anthropic.com/v1';

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key') ?: null;
        $this->chatModel = config('services.anthropic.model_chat', 'claude-opus-4-20250514');
        $this->summarizeModel = config('services.anthropic.model_summarize', 'claude-3-5-haiku-20241022');
    }

    /**
     * Stream a chat response from Claude.
     *
     * @return Generator<string>
     */
    public function streamChat(string $systemPrompt, array $messages, int $maxTokens = 4096): Generator
    {
        if (!$this->apiKey) {
            throw new AnthropicException('ANTHROPIC_API_KEY not configured');
        }

        // Format messages for Anthropic API
        $formattedMessages = [];
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->withOptions(['stream' => true])
            ->timeout(120)
            ->post("{$this->baseUrl}/messages", [
                'model' => $this->chatModel,
                'max_tokens' => $maxTokens,
                'system' => $systemPrompt,
                'messages' => $formattedMessages,
                'stream' => true,
            ]);

        if (!$response->successful()) {
            Log::error('Anthropic API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new AnthropicException(
                'Failed to get response from Claude',
                $response->status(),
                $response->body()
            );
        }

        $body = $response->body();
        
        // Parse SSE stream
        $lines = explode("\n", $body);
        foreach ($lines as $line) {
            if (str_starts_with($line, 'data: ')) {
                $data = substr($line, 6);
                if ($data === '[DONE]') {
                    break;
                }
                
                $json = json_decode($data, true);
                if ($json && isset($json['type'])) {
                    if ($json['type'] === 'content_block_delta' && isset($json['delta']['text'])) {
                        yield $json['delta']['text'];
                    }
                }
            }
        }
    }

    /**
     * Non-streaming chat for simple responses.
     */
    public function chat(string $systemPrompt, array $messages, int $maxTokens = 4096): string
    {
        if (!$this->apiKey) {
            throw new AnthropicException('ANTHROPIC_API_KEY not configured');
        }

        $formattedMessages = [];
        foreach ($messages as $message) {
            $formattedMessages[] = [
                'role' => $message['role'],
                'content' => $message['content'],
            ];
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(120)
            ->post("{$this->baseUrl}/messages", [
                'model' => $this->chatModel,
                'max_tokens' => $maxTokens,
                'system' => $systemPrompt,
                'messages' => $formattedMessages,
            ]);

        if (!$response->successful()) {
            Log::error('Anthropic API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new AnthropicException(
                'Failed to get response from Claude',
                $response->status(),
                $response->body()
            );
        }

        $json = $response->json();
        
        if (!isset($json['content'][0]['text'])) {
            throw new AnthropicException('Invalid response format from Claude');
        }

        return $json['content'][0]['text'];
    }

    /**
     * Summarize conversation for context compression.
     */
    public function summarize(array $messages): string
    {
        if (!$this->apiKey) {
            throw new AnthropicException('ANTHROPIC_API_KEY not configured');
        }

        $conversationText = "";
        foreach ($messages as $message) {
            $role = ucfirst($message['role']);
            $conversationText .= "{$role}: {$message['content']}\n\n";
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])
            ->timeout(60)
            ->post("{$this->baseUrl}/messages", [
                'model' => $this->summarizeModel,
                'max_tokens' => 1024,
                'system' => 'You are a summarizer. Summarize the following PRD creation conversation, preserving key decisions, requirements, and context. Be concise but complete.',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Summarize this conversation:\n\n{$conversationText}",
                    ],
                ],
            ]);

        if (!$response->successful()) {
            Log::error('Anthropic summarize error', [
                'status' => $response->status(),
            ]);
            throw new AnthropicException('Failed to summarize conversation');
        }

        $json = $response->json();
        return $json['content'][0]['text'] ?? '';
    }

    /**
     * Estimate token count (rough approximation).
     */
    public function estimateTokens(string $text): int
    {
        // Claude uses roughly 4 characters per token
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get the PRD system prompt.
     */
    public function getPrdSystemPrompt(string $prdContent): string
    {
        return <<<PROMPT
You are an expert product manager and PRD (Product Requirements Document) specialist. You help users create comprehensive, well-structured PRDs.

## Your Role
- Help refine and expand product requirements
- Suggest improvements to existing PRD sections
- Ask clarifying questions when requirements are vague
- Provide best practices for PRD writing
- When suggesting PRD updates, format them clearly so they can be applied

## Current PRD Content
{$prdContent}

## Response Format
When suggesting updates to the PRD, use this format:
<prd_update>
[Your suggested markdown content that should replace or be added to the PRD]
</prd_update>

Only use <prd_update> tags when you have a concrete suggestion to modify the PRD.
For general discussion or questions, respond normally without the tags.

Be concise, professional, and focused on creating an excellent PRD.
PROMPT;
    }
}

<?php
namespace Src\AIMediation\Infrastructure\Services;

use Illuminate\Support\Facades\Http;
use Src\AIMediation\Domain\Exceptions\AiProviderException;
use Src\AIMediation\Domain\Exceptions\AiProviderKeyMissingException;
use Src\AIMediation\Domain\Provider\AiEvaluatorClient;
use Src\AIMediation\Domain\Provider\AiTextGeneratorClient;

/**
 * Real integration against the Anthropic Messages API — never mocked. If
 * ANTHROPIC_API_KEY isn't configured, this throws immediately rather than
 * returning a fabricated result; a missing key must surface as "evaluation
 * is unavailable," never as a silent fake pass/fail.
 */
final class ClaudeApiClient implements AiEvaluatorClient, AiTextGeneratorClient
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MAX_TOKENS = 4096;

    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model,
    ) {}

    /**
     * Translates the neutral $contentBlocks (built by EvaluationPromptBuilder)
     * into Anthropic's own content-block shape before sending.
     */
    public function evaluate(string $systemPrompt, array $contentBlocks): array
    {
        if ($this->apiKey === null || trim($this->apiKey) === '') {
            throw new AiProviderKeyMissingException('ANTHROPIC_API_KEY');
        }

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(120)->post(self::API_URL, [
            'model'      => $this->model,
            'max_tokens' => self::MAX_TOKENS,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => array_map($this->translateBlock(...), $contentBlocks)],
            ],
        ]);

        if ($response->failed()) {
            $body = $response->json('error.message') ?? $response->body();
            throw new AiProviderException('Claude', "HTTP {$response->status()}: {$body}");
        }

        $text = $response->json('content.0.text');
        if ($text === null) {
            throw new AiProviderException('Claude', 'response contained no text content');
        }

        return $this->parseJson($text);
    }

    /** Same request shape as evaluate(), but returns the model's raw text untouched — no JSON coercion. */
    public function generate(string $systemPrompt, array $contentBlocks): string
    {
        if ($this->apiKey === null || trim($this->apiKey) === '') {
            throw new AiProviderKeyMissingException('ANTHROPIC_API_KEY');
        }

        $response = Http::withHeaders([
            'x-api-key'         => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->timeout(120)->post(self::API_URL, [
            'model'      => $this->model,
            'max_tokens' => self::MAX_TOKENS,
            'system'     => $systemPrompt,
            'messages'   => [
                ['role' => 'user', 'content' => array_map($this->translateBlock(...), $contentBlocks)],
            ],
        ]);

        if ($response->failed()) {
            $body = $response->json('error.message') ?? $response->body();
            throw new AiProviderException('Claude', "HTTP {$response->status()}: {$body}");
        }

        $text = $response->json('content.0.text');
        if ($text === null) {
            throw new AiProviderException('Claude', 'response contained no text content');
        }

        return trim($text);
    }

    private function translateBlock(array $block): array
    {
        if ($block['type'] === 'text') {
            return ['type' => 'text', 'text' => $block['text']];
        }

        // image or document — both use Anthropic's base64 "source" shape.
        return [
            'type'   => $block['type'],
            'source' => [
                'type'       => 'base64',
                'media_type' => $block['mediaType'],
                'data'       => $block['data'],
            ],
        ];
    }

    private function parseJson(string $text): array
    {
        $text = trim($text);

        // Claude sometimes wraps JSON in a markdown fence even when told not to.
        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text);
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            throw new AiProviderException('Claude', 'response was not valid JSON: ' . substr($text, 0, 200));
        }

        return $decoded;
    }
}

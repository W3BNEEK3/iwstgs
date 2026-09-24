<?php
namespace Src\AIMediation\Infrastructure\Services;

use Illuminate\Support\Facades\Http;
use Src\AIMediation\Domain\Exceptions\AiProviderException;
use Src\AIMediation\Domain\Exceptions\AiProviderKeyMissingException;
use Src\AIMediation\Domain\Provider\AiEvaluatorClient;
use Src\AIMediation\Domain\Provider\AiTextGeneratorClient;

/**
 * Real integration against Google's Generative Language API (Gemini) — one
 * of the AI layer's supported providers, selected explicitly via
 * AI_EVALUATION_PROVIDER rather than as a silent automatic fallback, since a
 * mid-evaluation silent provider switch would make grading inconsistency
 * very hard to reason about.
 */
final class GeminiApiClient implements AiEvaluatorClient, AiTextGeneratorClient
{
    private const API_URL_TEMPLATE = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';
    private const MAX_OUTPUT_TOKENS = 8192;

    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $model,
    ) {}

    /**
     * Translates the neutral $contentBlocks (built by EvaluationPromptBuilder)
     * into Gemini's own "parts" shape before sending.
     */
    public function evaluate(string $systemPrompt, array $contentBlocks): array
    {
        if ($this->apiKey === null || trim($this->apiKey) === '') {
            throw new AiProviderKeyMissingException('GEMINI_API_KEY');
        }

        $url = sprintf(self::API_URL_TEMPLATE, $this->model);

        $response = Http::withHeaders([
            'x-goog-api-key' => $this->apiKey,
            'content-type'   => 'application/json',
        ])->timeout(120)->post($url, [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [
                ['role' => 'user', 'parts' => array_map($this->translateBlock(...), $contentBlocks)],
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'maxOutputTokens'    => self::MAX_OUTPUT_TOKENS,
            ],
        ]);

        if ($response->failed()) {
            $body = $response->json('error.message') ?? $response->body();
            throw new AiProviderException('Gemini', "HTTP {$response->status()}: {$body}");
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        if ($text === null) {
            $finishReason = $response->json('candidates.0.finishReason');
            throw new AiProviderException('Gemini', 'response contained no text content' . ($finishReason ? " (finishReason: {$finishReason})" : ''));
        }

        return $this->parseJson($text);
    }

    /** Same request shape as evaluate(), but without response_mime_type and no JSON coercion of the result. */
    public function generate(string $systemPrompt, array $contentBlocks): string
    {
        if ($this->apiKey === null || trim($this->apiKey) === '') {
            throw new AiProviderKeyMissingException('GEMINI_API_KEY');
        }

        $url = sprintf(self::API_URL_TEMPLATE, $this->model);

        $response = Http::withHeaders([
            'x-goog-api-key' => $this->apiKey,
            'content-type'   => 'application/json',
        ])->timeout(120)->post($url, [
            'system_instruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [
                ['role' => 'user', 'parts' => array_map($this->translateBlock(...), $contentBlocks)],
            ],
            'generationConfig' => [
                'maxOutputTokens' => self::MAX_OUTPUT_TOKENS,
            ],
        ]);

        if ($response->failed()) {
            $body = $response->json('error.message') ?? $response->body();
            throw new AiProviderException('Gemini', "HTTP {$response->status()}: {$body}");
        }

        $text = $response->json('candidates.0.content.parts.0.text');
        if ($text === null) {
            $finishReason = $response->json('candidates.0.finishReason');
            throw new AiProviderException('Gemini', 'response contained no text content' . ($finishReason ? " (finishReason: {$finishReason})" : ''));
        }

        return trim($text);
    }

    private function translateBlock(array $block): array
    {
        if ($block['type'] === 'text') {
            return ['text' => $block['text']];
        }

        // image or document — Gemini uses inline_data for both, same shape.
        return [
            'inline_data' => [
                'mime_type' => $block['mediaType'],
                'data'      => $block['data'],
            ],
        ];
    }

    private function parseJson(string $text): array
    {
        $text = trim($text);

        if (str_starts_with($text, '```')) {
            $text = preg_replace('/^```(?:json)?\s*|\s*```$/', '', $text);
        }

        $decoded = json_decode($text, true);
        if (! is_array($decoded)) {
            throw new AiProviderException('Gemini', 'response was not valid JSON: ' . substr($text, 0, 200));
        }

        return $decoded;
    }
}

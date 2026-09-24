<?php
namespace Src\AIMediation\Domain\Provider;

/**
 * Same provider-neutral shape as AiEvaluatorClient, but for prose generation
 * rather than structured evaluation — the response is returned as raw text,
 * never JSON-decoded. Kept as a separate interface rather than extending
 * AiEvaluatorClient::evaluate() because the concrete clients force JSON
 * response parsing (and, for Gemini, a JSON response_mime_type) into that
 * method; forcing narrative prose through it would fail outright.
 */
interface AiTextGeneratorClient
{
    /** $contentBlocks: [{type: 'text', text}, {type: 'image'|'document', mediaType, data (base64)}, ...] */
    public function generate(string $systemPrompt, array $contentBlocks): string;
}

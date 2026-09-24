<?php
namespace Src\AIMediation\Domain\Provider;

/**
 * Abstraction over whichever AI provider is actually evaluating a
 * submission — the user wants Gemini available as a fallback when Claude
 * usage runs out, so EvaluationService depends on this interface rather
 * than a concrete client. $contentBlocks is provider-neutral (built by
 * EvaluationPromptBuilder): [{type: 'text', text}, {type: 'image'|
 * 'document', mediaType, data (base64)}, ...] — each implementation
 * translates these into its own API's request shape internally.
 */
interface AiEvaluatorClient
{
    /** @return array the parsed JSON response body, already decoded */
    public function evaluate(string $systemPrompt, array $contentBlocks): array;
}

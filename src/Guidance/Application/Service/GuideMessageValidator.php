<?php
namespace Src\Guidance\Application\Service;

/**
 * The guard between the AI and a learner's screen. A written message must
 * parse, fit the card, and must not contain code (Tiroco never hands over a
 * solution), links (links come only from the trigger's own call to action)
 * or platform jargon. Anything that fails is discarded and the authored
 * fallback is shown instead.
 */
final class GuideMessageValidator
{
    public const MAX_TITLE_CHARS = 70;
    public const MAX_BODY_WORDS = 75;
    public const MAX_BODY_CHARS = 480;

    private const JARGON = ['/\bCAC\b/', '/\bgap[_ ]type\b/i', '/\bdim_[a-z_]+/', '/\b(strategy|knowledge)_gap\b/'];

    private const CODE = [
        '/`/',
        '/[{};]\s*$/m',
        '/=>|->|::|\+\+|===|!==/',
        '/\b(function|const|let|var|return|public|private|def|class|import|SELECT|INSERT|UPDATE|DELETE)\b\s*[\w$(]*\s*[({=]/',
        '/<\/?[a-z][^>]*>/i',
    ];

    /**
     * @return array{title: string, body: string}|null null when the output must not be shown,
     *   or when the writer chose to skip ({"skip": true})
     */
    public function parse(string $raw): ?array
    {
        $raw = trim($raw);
        $raw = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $raw);
        if (preg_match('/\{.*\}/s', $raw, $m)) {
            $raw = $m[0];
        }

        $data = json_decode($raw, true);
        if (! is_array($data) || ! empty($data['skip'])) {
            return null;
        }

        $title = trim((string) ($data['title'] ?? ''));
        $body = trim(preg_replace('/[ \t]+/', ' ', (string) ($data['body'] ?? '')));

        return $this->isAcceptable($title, $body) ? ['title' => $title, 'body' => $body] : null;
    }

    public function isAcceptable(string $title, string $body): bool
    {
        if ($title === '' || mb_strlen($title) > self::MAX_TITLE_CHARS || str_contains($title, "\n")) {
            return false;
        }
        if (mb_strlen($body) < 20 || mb_strlen($body) > self::MAX_BODY_CHARS || str_word_count($body) > self::MAX_BODY_WORDS) {
            return false;
        }
        if (preg_match('~https?://|www\.|\]\(~i', $title . ' ' . $body)) {
            return false;
        }
        foreach ([...self::JARGON, ...self::CODE] as $pattern) {
            if (preg_match($pattern, $title . "\n" . $body)) {
                return false;
            }
        }

        return true;
    }
}

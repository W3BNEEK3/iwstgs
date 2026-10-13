<?php
namespace Src\SourceControl\Application\Service;

use Src\SourceControl\Domain\Comparison;
use Src\SourceControl\Domain\DiffSnapshot;

/**
 * Turns a comparison into what the AI reviewer sees (design doc v2-01 §2.2,
 * §10): secrets, dependencies and generated files are never sent, and the
 * diff is trimmed to a budget so one huge file can't crowd out the rest.
 */
final class DiffBuilder
{
    public const BUDGET_BYTES = 60_000;

    private const EXCLUDED = [
        '#(^|/)\.env($|\.)#', '#\.(pem|key|p12|pfx)$#i', '#(^|/)node_modules/#', '#(^|/)vendor/#',
        '#(^|/)(package-lock\.json|yarn\.lock|pnpm-lock\.yaml|composer\.lock)$#', '#(^|/)(dist|build|\.next)/#',
        '#\.(db|sqlite|sqlite3)$#i', '#\.min\.(js|css)$#',
    ];

    public function build(Comparison $comparison): DiffSnapshot
    {
        $text = '';
        $files = [];
        $omitted = [];
        $truncated = false;

        foreach ($comparison->files as $file) {
            $included = ! $this->isExcluded($file['filename']);
            $files[] = [
                'filename'  => $file['filename'],
                'status'    => $file['status'],
                'additions' => $file['additions'],
                'deletions' => $file['deletions'],
                'included'  => $included,
            ];
            if (! $included) {
                $omitted[] = $file['filename'];
                continue;
            }

            $chunk = "--- {$file['filename']} ({$file['status']}, +{$file['additions']} -{$file['deletions']})\n"
                . ($file['patch'] ?? '(binary or too large to show)') . "\n\n";
            if (strlen($text) + strlen($chunk) > self::BUDGET_BYTES) {
                $truncated = true;
                $text .= "--- {$file['filename']} (not shown: review budget reached)\n";
                continue;
            }
            $text .= $chunk;
        }

        return new DiffSnapshot(trim($text), [
            'files'     => $files,
            'commits'   => $comparison->commitCount,
            'truncated' => $truncated,
            'omitted'   => $omitted,
        ]);
    }

    private function isExcluded(string $filename): bool
    {
        foreach (self::EXCLUDED as $pattern) {
            if (preg_match($pattern, $filename)) {
                return true;
            }
        }

        return false;
    }
}

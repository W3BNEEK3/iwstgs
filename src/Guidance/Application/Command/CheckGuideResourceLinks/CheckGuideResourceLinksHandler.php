<?php
namespace Src\Guidance\Application\Command\CheckGuideResourceLinks;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\Http;
use Src\Guidance\Domain\Content\GuideContentRepository;
use Src\Guidance\Domain\Content\GuideResource;

/**
 * Checks every active resource link in parallel. A link that fails is
 * flagged for admins and no longer recommended until it passes again or
 * its URL is edited.
 */
final class CheckGuideResourceLinksHandler
{
    public function __construct(private readonly GuideContentRepository $content) {}

    public function handle(CheckGuideResourceLinksCommand $command): void
    {
        $resources = $this->content->resources(activeOnly: true);
        if ($resources === []) {
            return;
        }

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (GuideResource $r) => $pool->as($r->id)
                ->timeout(10)
                ->withHeaders(['User-Agent' => 'AreynaLinkCheck/1.0'])
                ->get($r->url),
            $resources,
        ));

        foreach ($resources as $resource) {
            $response = $responses[$resource->id] ?? null;
            // Sites behind bot protection answer an automated check with 401/403/429 while
            // working fine in a browser; only a missing page or a dead server counts as broken.
            $ok = $response instanceof \Illuminate\Http\Client\Response
                && ($response->status() < 400 || in_array($response->status(), [401, 403, 429], true));
            $this->content->recordLinkCheck($resource->id, $ok);
        }
    }
}

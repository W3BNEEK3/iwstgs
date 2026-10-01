<?php
namespace Src\Guidance\Application\Query\GetNextGuideMessage;

use Src\Guidance\Application\Service\GuideDelivery;
use Src\Guidance\Application\Service\VisitGuideTriggers;
use Src\Guidance\Domain\Message\GuideKind;
use Src\Guidance\Domain\Message\TriggerCatalog;

/**
 * Asked by the guide card after each page has loaded (never while the page
 * renders). Unlike most queries, answering it has effects by design: the
 * visit is what fires welcome-back, tip and announcement triggers, and the
 * returned message counts as shown — delivery *is* the read.
 */
final class GetNextGuideMessageHandler
{
    public function __construct(
        private readonly VisitGuideTriggers $visitTriggers,
        private readonly GuideDelivery $delivery,
    ) {}

    public function handle(GetNextGuideMessageQuery $query): ?GuideMessageView
    {
        $this->visitTriggers->observe($query->userId, $query->page, $query->userCreatedAt);

        $message = $this->delivery->next($query->userId, $query->page);
        if ($message === null) {
            return null;
        }

        return new GuideMessageView(
            id:                  $message->id,
            kind:                $message->kind->value,
            title:               $message->title,
            body:                $message->body,
            ctaLabel:            $message->ctaUrl !== null ? $message->ctaLabel : null,
            ctaUrl:              $message->ctaUrl,
            ctaIsExternal:       $message->ctaUrl !== null && str_starts_with($message->ctaUrl, 'http'),
            why:                 TriggerCatalog::find($message->triggerKey)['why'] ?? '',
            canOptOutOfResource: $message->kind === GuideKind::Resource && $message->resourceId !== null,
        );
    }
}

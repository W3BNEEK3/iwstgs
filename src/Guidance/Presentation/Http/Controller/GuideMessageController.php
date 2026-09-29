<?php
namespace Src\Guidance\Presentation\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\Guidance\Application\Command\DismissGuideMessage\DismissGuideMessageCommand;
use Src\Guidance\Application\Command\OptOutOfResource\OptOutOfResourceCommand;
use Src\Guidance\Application\Command\RateGuideMessage\RateGuideMessageCommand;
use Src\Guidance\Application\Command\ReportStuckOnTask\ReportStuckOnTaskCommand;
use Src\Guidance\Application\Command\SetGuideKindsMuted\SetGuideKindsMutedCommand;
use Src\Guidance\Application\Query\GetNextGuideMessage\GetNextGuideMessageQuery;
use Src\Guidance\Application\Query\GetNextGuideMessage\GuideMessageView;
use Src\Guidance\Application\Query\ListWhatsNew\ListWhatsNewQuery;
use Src\Guidance\Domain\Message\GuideKind;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;

/** Tiroco's own messages: fetched by the guide card after a page loads, and the learner's reactions to them. */
class GuideMessageController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function next(Request $request): JsonResponse
    {
        $page = $request->query('page');
        $user = Auth::user();

        /** @var GuideMessageView|null $message */
        $message = $this->queryBus->ask(new GetNextGuideMessageQuery(
            (string) $user->getAuthIdentifier(),
            is_string($page) && preg_match('/^[a-z0-9-]{1,40}$/', $page) ? $page : null,
            $user->created_at ?? now(),
        ));

        return response()->json(['message' => $message?->toArray()]);
    }

    public function dismiss(string $message): Response
    {
        $this->commandBus->dispatch(new DismissGuideMessageCommand((string) Auth::id(), $message));

        return response()->noContent();
    }

    public function rate(Request $request, string $message): Response
    {
        $data = $request->validate(['helpful' => ['required', 'boolean']]);
        $this->commandBus->dispatch(new RateGuideMessageCommand((string) Auth::id(), $message, (bool) $data['helpful']));

        return response()->noContent();
    }

    public function optOut(Request $request, string $message): Response
    {
        $data = $request->validate(['reason' => ['required', 'in:already_use,not_for_me']]);
        $this->commandBus->dispatch(new OptOutOfResourceCommand((string) Auth::id(), $message, $data['reason']));

        return response()->noContent();
    }

    public function stuck(Request $request): Response
    {
        $data = $request->validate([
            'session' => ['required', 'uuid'],
            'task'    => ['required', 'uuid'],
            'minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        ]);
        $this->commandBus->dispatch(new ReportStuckOnTaskCommand((string) Auth::id(), $data['session'], $data['task'], (int) $data['minutes']));

        return response()->noContent();
    }

    public function updateKinds(Request $request): RedirectResponse
    {
        $data = $request->validate(['kinds' => ['array'], 'kinds.*' => ['string']]);
        $on = $data['kinds'] ?? [];
        $muted = array_values(array_filter(
            array_map(fn (GuideKind $k) => $k->value, GuideKind::cases()),
            fn (string $kind) => ! in_array($kind, $on, true),
        ));

        $this->commandBus->dispatch(new SetGuideKindsMutedCommand((string) Auth::id(), $muted));

        return back()->with('success', 'Guide preferences saved.');
    }

    public function whatsNew(): View
    {
        return view('guidance.whats-new', ['announcements' => $this->queryBus->ask(new ListWhatsNewQuery())]);
    }
}

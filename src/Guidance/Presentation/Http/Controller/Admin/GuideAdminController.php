<?php
namespace Src\Guidance\Presentation\Http\Controller\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Src\Competency\Application\Query\ListCompetenceDimensions\ListCompetenceDimensionsQuery;
use Src\Guidance\Application\Command\CheckGuideResourceLinks\CheckGuideResourceLinksCommand;
use Src\Guidance\Application\Command\DeleteGuideContent\DeleteGuideContentCommand;
use Src\Guidance\Application\Command\GenerateAnnouncementSummary\GenerateAnnouncementSummaryCommand;
use Src\Guidance\Application\Command\SaveGuideContent\SaveGuideContentCommand;
use Src\Guidance\Application\Command\SetAnnouncementStatus\SetAnnouncementStatusCommand;
use Src\Guidance\Application\Command\UpdateGuideSettings\UpdateGuideSettingsCommand;
use Src\Guidance\Application\Query\GetGuideContentItem\GetGuideContentItemQuery;
use Src\Guidance\Application\Query\GetGuideHealth\GetGuideHealthQuery;
use Src\Guidance\Application\Query\ListGuideContent\ListGuideContentQuery;
use Src\Guidance\Domain\Content\AnnouncementNotReady;
use Src\Guidance\Domain\Content\GuideResource;
use Src\Guidance\Domain\GuideCatalog;
use Src\Guidance\Domain\Message\TriggerCatalog;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;

/** Admin → Guide (design doc v2-05 §9). */
class GuideAdminController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    // ---- Overview: triggers, caps, health, message log ----------------------

    public function index(Request $request): View
    {
        $trigger = $request->query('trigger');

        return view('admin.guide.index', [
            'health'  => $this->queryBus->ask(new GetGuideHealthQuery(30, is_string($trigger) && TriggerCatalog::find($trigger) ? $trigger : null)),
            'trigger' => $trigger,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'daily_cap'                    => ['required', 'integer', 'min:0', 'max:20'],
            'triggers'                     => ['array'],
            'triggers.*.enabled'           => ['nullable', 'boolean'],
            'triggers.*.cooldown_hours'    => ['required', 'integer', 'min:0', 'max:8760'],
        ]);

        $triggers = [];
        foreach (array_keys(TriggerCatalog::all()) as $key) {
            $triggers[$key] = [
                // The form always posts every trigger (unchecked boxes send 0); one left out stays on.
                'enabled'        => (bool) ($data['triggers'][$key]['enabled'] ?? true),
                'cooldown_hours' => (int) ($data['triggers'][$key]['cooldown_hours'] ?? TriggerCatalog::find($key)['cooldown_hours']),
            ];
        }

        $this->commandBus->dispatch(new UpdateGuideSettingsCommand((int) $data['daily_cap'], $triggers));

        return back()->with('success', 'Guide settings saved.');
    }

    // ---- Tips -----------------------------------------------------------------

    public function tips(): View
    {
        return view('admin.guide.tips', [
            'tips'  => $this->queryBus->ask(new ListGuideContentQuery('tips')),
            'pages' => array_values(array_unique(GuideCatalog::ROUTE_PAGES)),
        ]);
    }

    public function storeTip(Request $request): RedirectResponse
    {
        $this->commandBus->dispatch(new SaveGuideContentCommand('tip', null, $this->tipData($request)));

        return back()->with('success', 'Tip added.');
    }

    public function updateTip(Request $request, string $tip): RedirectResponse
    {
        $this->commandBus->dispatch(new SaveGuideContentCommand('tip', $tip, $this->tipData($request)));

        return back()->with('success', 'Tip saved.');
    }

    public function destroyTip(string $tip): RedirectResponse
    {
        $this->commandBus->dispatch(new DeleteGuideContentCommand('tip', $tip));

        return back()->with('success', 'Tip deleted.');
    }

    // ---- Resources ------------------------------------------------------------

    public function resources(): View
    {
        return view('admin.guide.resources', [
            'resources'  => $this->queryBus->ask(new ListGuideContentQuery('resources')),
            'dimensions' => $this->dimensionLabels(),
        ]);
    }

    public function createResource(): View
    {
        return view('admin.guide.resource-form', ['resource' => null, 'dimensions' => $this->dimensionLabels()]);
    }

    public function storeResource(Request $request): RedirectResponse
    {
        $this->commandBus->dispatch(new SaveGuideContentCommand('resource', null, $this->resourceData($request)));

        return redirect()->route('admin.guide.resources')->with('success', 'Resource added.');
    }

    public function editResource(string $resource): View
    {
        $item = $this->queryBus->ask(new GetGuideContentItemQuery('resource', $resource));
        abort_if($item === null, 404);

        return view('admin.guide.resource-form', ['resource' => $item, 'dimensions' => $this->dimensionLabels()]);
    }

    public function updateResource(Request $request, string $resource): RedirectResponse
    {
        $this->commandBus->dispatch(new SaveGuideContentCommand('resource', $resource, $this->resourceData($request)));

        return redirect()->route('admin.guide.resources')->with('success', 'Resource saved.');
    }

    public function destroyResource(string $resource): RedirectResponse
    {
        $this->commandBus->dispatch(new DeleteGuideContentCommand('resource', $resource));

        return redirect()->route('admin.guide.resources')->with('success', 'Resource deleted.');
    }

    public function checkLinks(): RedirectResponse
    {
        $this->commandBus->dispatch(new CheckGuideResourceLinksCommand());

        return back()->with('success', 'Links checked. Any marked "Broken" are no longer recommended until fixed.');
    }

    // ---- Announcements --------------------------------------------------------

    public function announcements(): View
    {
        return view('admin.guide.announcements', ['announcements' => $this->queryBus->ask(new ListGuideContentQuery('announcements'))]);
    }

    public function createAnnouncement(): View
    {
        return view('admin.guide.announcement-form', ['announcement' => null]);
    }

    public function storeAnnouncement(Request $request): RedirectResponse
    {
        $id = (string) Str::uuid();
        $this->commandBus->dispatch(new SaveGuideContentCommand('announcement', $id, $this->announcementData($request), (string) Auth::id()));

        return redirect()->route('admin.guide.announcements.edit', $id)
            ->with('success', 'Draft saved. Generate or write the learner-facing text, then publish.');
    }

    public function editAnnouncement(string $announcement): View
    {
        $item = $this->queryBus->ask(new GetGuideContentItemQuery('announcement', $announcement));
        abort_if($item === null, 404);

        return view('admin.guide.announcement-form', ['announcement' => $item]);
    }

    public function updateAnnouncement(Request $request, string $announcement): RedirectResponse
    {
        $this->commandBus->dispatch(new SaveGuideContentCommand('announcement', $announcement, $this->announcementData($request)));

        return back()->with('success', 'Announcement saved.');
    }

    public function generateAnnouncement(string $announcement): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new GenerateAnnouncementSummaryCommand($announcement));
        } catch (AnnouncementNotReady $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Learner summary generated. Check it, edit if needed, then publish.');
    }

    public function announcementStatus(Request $request, string $announcement): RedirectResponse
    {
        $status = $request->validate(['status' => ['required', 'in:draft,published,archived']])['status'];

        try {
            $this->commandBus->dispatch(new SetAnnouncementStatusCommand($announcement, $status));
        } catch (AnnouncementNotReady $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', match ($status) {
            'published' => 'Published. Learners will see it once, on their next page.',
            'archived'  => 'Archived. It no longer shows anywhere.',
            default     => 'Moved back to draft.',
        });
    }

    // ---- Validation -----------------------------------------------------------

    private function tipData(Request $request): array
    {
        $data = $request->validate([
            'area'      => ['required', 'string', 'max:40'],
            'text'      => ['required', 'string', 'max:300'],
            'pages'     => ['nullable', 'array'],
            'pages.*'   => ['string', Rule::in(array_values(GuideCatalog::ROUTE_PAGES))],
            'is_active' => ['nullable', 'boolean'],
        ]);

        return [
            'area'      => $data['area'],
            'text'      => $data['text'],
            'pages'     => ($data['pages'] ?? []) === [] ? null : array_values($data['pages']),
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function resourceData(Request $request): array
    {
        $data = $request->validate([
            'name'         => ['required', 'string', 'max:120'],
            'url'          => ['required', 'url:https,http', 'max:500'],
            'kind'         => ['required', Rule::in(GuideResource::KINDS)],
            'dimensions'   => ['required', 'array', 'min:1'],
            'dimensions.*' => ['string', Rule::in(array_keys($this->dimensionLabels()))],
            'level'        => ['required', Rule::in(GuideResource::LEVELS)],
            'is_free'      => ['nullable', 'boolean'],
            'blurb'        => ['required', 'string', 'max:300'],
            'is_active'    => ['nullable', 'boolean'],
        ]);

        return [
            'name'       => $data['name'],
            'url'        => $data['url'],
            'kind'       => $data['kind'],
            'dimensions' => array_values($data['dimensions']),
            'level'      => $data['level'],
            'is_free'    => (bool) ($data['is_free'] ?? false),
            'blurb'      => $data['blurb'],
            'is_active'  => (bool) ($data['is_active'] ?? false),
        ];
    }

    private function announcementData(Request $request): array
    {
        $data = $request->validate([
            'internal_title' => ['required', 'string', 'max:150'],
            'notes'          => ['required', 'string', 'max:5000'],
            'title'          => ['nullable', 'string', 'max:80'],
            'body'           => ['nullable', 'string', 'max:400'],
            // Internal paths only: "//host" would be an external, protocol-relative link.
            'link_url'       => ['nullable', 'string', 'max:300', 'regex:#^/(?!/)[A-Za-z0-9/_\-.?=&%]*$#'],
            'feature_flag'   => ['nullable', 'string', 'max:100', 'exists:feature_flags,flag_key'],
        ], ['link_url.regex' => 'Use a path inside Areyna, starting with "/", e.g. /learn/profile.']);

        return [
            'internal_title' => $data['internal_title'],
            'notes'          => $data['notes'],
            'title'          => $data['title'] ?? null,
            'body'           => $data['body'] ?? null,
            'link_url'       => $data['link_url'] ?? null,
            'feature_flag'   => $data['feature_flag'] ?? null,
        ];
    }

    /** @return array<string, string> dimension id => label */
    private function dimensionLabels(): array
    {
        $labels = [];
        foreach ($this->queryBus->ask(new ListCompetenceDimensionsQuery()) as $dimension) {
            $labels[$dimension->id] = $dimension->shortLabel;
        }

        return $labels;
    }
}

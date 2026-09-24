<?php
namespace Src\AIMediation\Presentation\Http\Controller\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Src\AIMediation\Application\Command\ResolveReview\ResolveReviewCommand;
use Src\AIMediation\Application\Command\StartReview\StartReviewCommand;
use Src\AIMediation\Application\Query\ListPendingReviews\ListPendingReviewsQuery;
use Src\AIMediation\Domain\Exceptions\InvalidReviewTransitionException;
use Src\AIMediation\Domain\Exceptions\ReviewEntryNotFoundException;
use Src\AIMediation\Presentation\Http\Request\ResolveReviewRequest;
use Src\Shared\Application\Bus\CommandBus;
use Src\Shared\Application\Bus\QueryBus;

class HumanReviewController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly QueryBus $queryBus,
    ) {}

    public function index(): View
    {
        $reviews = $this->queryBus->ask(new ListPendingReviewsQuery());

        return view('admin.human-review.index', ['reviews' => $reviews]);
    }

    public function start(string $review): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new StartReviewCommand($review, Auth::id()));
        } catch (ReviewEntryNotFoundException $e) {
            abort(404);
        } catch (InvalidReviewTransitionException $e) {
            return back()->with('error', ucfirst($e->getMessage()) . '.');
        }

        return redirect()->route('admin.human-review.index')->with('success', 'Review started.');
    }

    public function resolve(ResolveReviewRequest $request, string $review): RedirectResponse
    {
        try {
            $this->commandBus->dispatch(new ResolveReviewCommand(
                reviewEntryId: $review,
                decision:      $request->validated('decision'),
                notes:         $request->validated('notes'),
            ));
        } catch (ReviewEntryNotFoundException $e) {
            abort(404);
        } catch (InvalidReviewTransitionException $e) {
            return back()->with('error', ucfirst($e->getMessage()) . '.');
        }

        return redirect()->route('admin.human-review.index')->with('success', 'Review resolved.');
    }
}

<?php
namespace Src\EvalEngine\Presentation\Http\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Src\EvalEngine\Application\Command\AnswerFollowUpPrompt\AnswerFollowUpPromptCommand;
use Src\Shared\Application\Bus\CommandBus;

/**
 * Gap 2 — Learner-facing endpoint for submitting an answer to a follow-up
 * clarification prompt. Triggered when the learner sees the follow-up prompt
 * card on their sprint board (follow_up_status = 'pending') and submits a
 * response.
 *
 * Route: POST /submissions/{submissionId}/follow-up
 *
 * Auth: learner must own the submission (enforced in route middleware).
 */
final class FollowUpPromptController extends Controller
{
    public function __construct(private readonly CommandBus $commandBus) {}

    public function store(Request $request, string $submissionId): JsonResponse
    {
        $validated = $request->validate([
            'evaluation_id'      => ['required', 'uuid'],
            'answer_text'        => ['required', 'string', 'max:5000'],
        ]);

        $this->commandBus->dispatch(new AnswerFollowUpPromptCommand(
            evaluationId:      $validated['evaluation_id'],
            submissionId:      $submissionId,
            learnerId:         $request->user()->learner_id,
            learnerSessionId:  $request->input('session_id', ''), // passed by the experience layer
            answerText:        $validated['answer_text'],
        ));

        return response()->json(['message' => 'Answer submitted. Your evaluation is being updated.'], 200);
    }
}

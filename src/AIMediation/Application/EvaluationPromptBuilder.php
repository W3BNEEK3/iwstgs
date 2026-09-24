<?php
namespace Src\AIMediation\Application;

use Illuminate\Support\Facades\Storage;
use Src\AIMediation\Domain\Exceptions\SubmissionNotFoundForEvaluationException;
use Src\Shared\Application\Bus\QueryBus;
use Src\Simulation\Application\Query\GetScenario\GetScenarioQuery;
use Src\Simulation\Application\Query\GetTask\GetTaskQuery;
use Src\Simulation\Application\Query\ListCriteriaByTask\ListCriteriaByTaskQuery;
use Src\Simulation\Domain\Rubric\RubricCriterion;
use Src\Simulation\Domain\Scenario\ReferenceMaterial;
use Src\Simulation\Domain\Scenario\ScenarioTemplate;
use Src\Simulation\Domain\Task\ExpectedDeliverable;
use Src\Simulation\Domain\Task\KnowledgeAnchor;
use Src\Simulation\Domain\Task\Task;
use Src\Submission\Application\Query\GetSubmissionDetail\GetSubmissionDetailQuery;
use Src\Submission\Application\Query\GetSubmissionDetail\SubmissionDetailView;
use Src\Submission\Domain\Submission\SubmissionArtifactDetail;

/**
 * Assembles the evaluation prompt from every source Integration Spec §9/§11
 * calls for: submission layers, rubric criteria with claude_detection_hint,
 * scenario reference material embedded_signals, model_response_summary, and
 * knowledge anchor application_expectation fields.
 *
 * Artifact files that are images or PDFs are sent as real content blocks
 * (base64, in a provider-neutral shape each AiEvaluatorClient translates
 * itself), so the model actually reads them rather than seeing a filename.
 * .doc/.docx uploads are referenced by filename only — parsing legacy Word
 * format needs a dedicated library this project doesn't have; that's a
 * disclosed, narrow gap, not a silent one.
 */
final class EvaluationPromptBuilder
{
    public function __construct(private readonly QueryBus $queryBus) {}

    public function systemPrompt(): string
    {
        return <<<'PROMPT'
            You are the evaluation engine for Areyna, a professional software engineering
            training simulator. You grade a learner's submitted work against a fixed rubric
            authored by a human content designer.

            Critical rule: the rubric is the sole source of truth. You do not invent your own
            criteria, standards, or opinions about code quality. If your judgment would
            disagree with what a criterion's description says, the rubric wins — grade
            strictly against the criterion text and its tier descriptions. Your job is
            detection and mapping, not independent assessment.

            For each dimension present in the rubric criteria, determine which performance
            tier the submission achieves: beginning, developing, proficient, or distinguished
            (defined per-criterion by the distinguished/proficient/developing/beginning
            description fields you're given). List which specific criteria were met and which
            were missed, using the claude_detection_hint fields to guide what to look for.

            If you are not confident in your assessment for any dimension — ambiguous
            evidence, contradictory signals, or work that doesn't clearly map to any tier —
            set is_uncertain to true rather than guessing.

            For each dimension, also record layer_scores: which of the submission's four
            layers (written explanation, artifacts, code, planning snapshot) actually
            contained evidence you used to judge that dimension. Mark a layer
            "not_applicable" if it was empty or irrelevant to that criterion.

            Respond with ONLY a single JSON object, no markdown fences, no commentary, in
            exactly this shape:
            {
              "dimensions": [
                {
                  "dimension_id": "dim_001",
                  "task_dimension_label": "string, copied from the matching criterion",
                  "tier_achieved": "beginning|developing|proficient|distinguished",
                  "criteria_met": ["criterion_text values that were satisfied"],
                  "criteria_missed": ["criterion_text values that were not satisfied"],
                  "layer_scores": {
                    "layer1_text": "contributed|not_applicable",
                    "layer2_artifacts": "contributed|not_applicable",
                    "layer3_code": "contributed|not_applicable",
                    "layer4_planning": "contributed|not_applicable"
                  },
                  "evaluator_notes": "brief justification"
                }
              ],
              "overall_tier": "beginning|developing|proficient|distinguished",
              "passes_threshold": true,
              "gap_type": "knowledge_gap|strategy_gap|null",
              "is_uncertain": false,
              "knowledge_anchors_detected": [
                {"concept_id": "string or null", "met": true}
              ],
              "concept_suggestions": [
                {
                  "concept_name": "string — human-readable concept name",
                  "domain": "string — knowledge domain, e.g. 'Software Engineering'",
                  "reason": "string — why this concept appears important for this task and is not already in the anchor index"
                }
              ]
            }

            The concept_suggestions field is OPTIONAL. Only populate it if the submission
            achieved 'distinguished' tier AND you noticed the learner demonstrating
            understanding of a concept that is NOT listed in the knowledge_anchors you
            were given. This is purely advisory for content curators — omit or leave
            as an empty array if no novel concepts are present.
            PROMPT;
    }

    /** @return array<int, array<string, mixed>> provider-neutral content blocks */
    public function userContent(string $submissionId): array
    {
        /** @var SubmissionDetailView|null $detail */
        $detail = $this->queryBus->ask(new GetSubmissionDetailQuery($submissionId));
        if ($detail === null) {
            throw new SubmissionNotFoundForEvaluationException($submissionId);
        }

        /** @var Task $task */
        $task = $this->queryBus->ask(new GetTaskQuery($detail->submission->taskId));

        /** @var RubricCriterion[] $criteria */
        $criteria = $this->queryBus->ask(new ListCriteriaByTaskQuery($detail->submission->taskId));
        $criteria = $this->filterCriteriaByComplexity($criteria, $detail->submission->cacComplexityAtSub);

        /** @var ScenarioTemplate|null $scenario */
        $scenario = $this->queryBus->ask(new GetScenarioQuery($detail->submission->scenarioId));

        $blocks = [
            ['type' => 'text', 'text' => $this->buildTextSection($task, $criteria, $scenario, $detail)],
        ];

        foreach ($detail->artifacts as $artifact) {
            $block = $this->artifactContentBlock($artifact);
            if ($block !== null) {
                $blocks[] = $block;
            }
        }

        return $blocks;
    }

    /**
     * BLD §10.2 — the same dimension is evaluated by genuinely different
     * criteria at different complexity tiers, not one criterion interpreted
     * more strictly. Grade against whichever tier the submission was
     * actually made at ($detail->submission->cacComplexityAtSub, stamped by
     * SubmitTaskHandler at submission time). Falls back to the full,
     * unfiltered list when no criterion exists at that exact tier — true for
     * every task today, since only 'mid' criteria are authored yet — so
     * grading is unaffected until low/high criteria are written.
     *
     * @param RubricCriterion[] $criteria
     * @return RubricCriterion[]
     */
    private function filterCriteriaByComplexity(array $criteria, string $complexityAtSub): array
    {
        $matching = array_values(array_filter(
            $criteria,
            fn (RubricCriterion $c) => $c->toPrimitives()['complexity_level'] === $complexityAtSub,
        ));

        return $matching !== [] ? $matching : $criteria;
    }

    private function buildTextSection(Task $task, array $criteria, ?ScenarioTemplate $scenario, SubmissionDetailView $detail): string
    {
        $taskPrimitives = $task->toPrimitives();
        $sections = [];

        $sections[] = "## Task\nTitle: {$taskPrimitives['title']}\nBrief: {$taskPrimitives['task_brief']}\nType: {$taskPrimitives['task_type']}"
            . ($taskPrimitives['domain'] ? "\nDomain: {$taskPrimitives['domain']}" : '');

        if ($taskPrimitives['model_response_summary']) {
            $sections[] = "## Model Response Summary (what a strong answer looks like)\n{$taskPrimitives['model_response_summary']}";
        }

        if ($scenario !== null && $scenario->referenceMaterials() !== []) {
            $refText = "## Reference Documents\n";
            foreach ($scenario->referenceMaterials() as $material) {
                /** @var ReferenceMaterial $material */
                $mp = $material->toPrimitives();
                $refText .= "- [{$mp['material_type']}] {$mp['title']}: {$mp['content']}\n";
                if (! empty($mp['embedded_signals'])) {
                    $refText .= '  Embedded signals (things the learner should notice): ' . implode('; ', $mp['embedded_signals']) . "\n";
                }
            }
            $sections[] = $refText;
        }

        if ($task->knowledgeAnchors() !== []) {
            $anchorText = "## Knowledge Anchors (concepts this task expects the learner to demonstrate)\n";
            foreach ($task->knowledgeAnchors() as $anchor) {
                /** @var KnowledgeAnchor $anchor */
                $ap = $anchor->toPrimitives();
                $anchorText .= "- concept_id={$ap['concept_id']} \"{$ap['concept_name']}\""
                    . ($ap['is_required'] ? ' (required)' : ' (optional)')
                    . ($ap['application_expectation'] ? ": expected application — {$ap['application_expectation']}" : '') . "\n";
            }
            $sections[] = $anchorText;
        }

        if ($criteria !== []) {
            $criteriaText = "## Rubric Criteria — the sole source of truth for grading\n";
            foreach ($criteria as $criterion) {
                /** @var RubricCriterion $criterion */
                $cp = $criterion->toPrimitives();
                $criteriaText .= "- dimension_id={$cp['parent_dimension_id']} label=\"{$cp['task_dimension_label']}\"\n"
                    . "  criterion_text: {$cp['criterion_text']}\n"
                    . "  claude_detection_hint: {$cp['claude_detection_hint']}\n"
                    . "  distinguished: {$cp['distinguished_description']}\n"
                    . "  proficient: {$cp['proficient_description']}\n"
                    . "  developing: {$cp['developing_description']}\n"
                    . "  beginning: {$cp['beginning_description']}\n";
            }
            $sections[] = $criteriaText;
        }

        $expectedDeliverables = $task->expectedDeliverables();
        if ($expectedDeliverables !== []) {
            $delivText = "## Expected Deliverables\n";
            foreach ($expectedDeliverables as $d) {
                /** @var ExpectedDeliverable $d */
                $dp = $d->toPrimitives();
                $delivText .= "- [{$dp['type']}] {$dp['label']}" . ($dp['is_required'] ? ' (required)' : ' (optional)') . "\n";
            }
            $sections[] = $delivText;
        }

        $submissionText = "## Submission (attempt {$detail->submission->attemptNumber})\n";
        if ($detail->submission->layer1Text) {
            $submissionText .= "### Written Explanation\n{$detail->submission->layer1Text}\n\n";
        }
        if ($detail->submission->layer3Code) {
            $submissionText .= "### Code\n```\n{$detail->submission->layer3Code}\n```\n\n";
        }
        $textArtifacts = array_filter($detail->artifacts, fn (SubmissionArtifactDetail $a) => in_array(pathinfo($a->filename, PATHINFO_EXTENSION), ['txt', 'md'], true));
        foreach ($textArtifacts as $artifact) {
            if (Storage::disk('local')->exists($artifact->storagePath)) {
                $submissionText .= "### Artifact: {$artifact->filename}\n" . Storage::disk('local')->get($artifact->storagePath) . "\n\n";
            }
        }
        $otherArtifacts = array_filter($detail->artifacts, fn (SubmissionArtifactDetail $a) => ! in_array(pathinfo($a->filename, PATHINFO_EXTENSION), ['txt', 'md', 'png', 'jpg', 'jpeg', 'pdf'], true));
        foreach ($otherArtifacts as $artifact) {
            $submissionText .= "(Artifact \"{$artifact->filename}\" was uploaded but its content could not be extracted for review — its format isn't supported for automatic text extraction.)\n";
        }
        $sections[] = $submissionText;

        if ($detail->submission->layer4PlanningSnapshot !== null) {
            $sections[] = "## Planning Layer Snapshot (context only, not itself graded unless a criterion references it)\n"
                . json_encode($detail->submission->layer4PlanningSnapshot, JSON_PRETTY_PRINT);
        }

        return implode("\n\n", $sections);
    }

    /**
     * Provider-neutral shape — each AiEvaluatorClient implementation
     * translates this into its own API's content-block format.
     */
    private function artifactContentBlock(SubmissionArtifactDetail $artifact): ?array
    {
        $extension = strtolower(pathinfo($artifact->filename, PATHINFO_EXTENSION));
        $mediaType = match ($extension) {
            'png'  => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'pdf'  => 'application/pdf',
            default => null,
        };

        if ($mediaType === null || ! Storage::disk('local')->exists($artifact->storagePath)) {
            return null;
        }

        return [
            'type'      => $extension === 'pdf' ? 'document' : 'image',
            'mediaType' => $mediaType,
            'data'      => base64_encode(Storage::disk('local')->get($artifact->storagePath)),
        ];
    }
}

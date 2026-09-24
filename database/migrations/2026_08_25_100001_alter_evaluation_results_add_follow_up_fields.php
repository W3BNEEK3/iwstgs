<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integration Spec §12 / BLD §8.4 — When an evaluation is uncertain, the
 * system should issue a follow-up clarification prompt to the *learner* before
 * escalating to human review. This was previously skipped — uncertain
 * evaluations went directly to the human_review_queue without any learner
 * interaction. These two columns enable the learner-facing follow-up flow:
 *
 *   follow_up_prompt_text  — the actual question shown to the learner
 *   follow_up_status       — tracks lifecycle: none → pending → answered
 *
 * Once status = answered, PostEvaluationRouter re-evaluates with the
 * combined original submission + follow-up answer. Only if still uncertain
 * after that does it escalate to human_review_queue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('evaluation_results', function (Blueprint $table) {
            $table->text('follow_up_prompt_text')
                ->nullable()
                ->after('is_uncertain')
                ->comment('Prompt shown to learner when evaluation is uncertain — null when not applicable');

            $table->enum('follow_up_status', ['none', 'pending', 'answered'])
                ->default('none')
                ->after('follow_up_prompt_text')
                ->comment('Lifecycle of the learner-facing follow-up prompt');
        });
    }

    public function down(): void
    {
        Schema::table('evaluation_results', function (Blueprint $table) {
            $table->dropColumn(['follow_up_prompt_text', 'follow_up_status']);
        });
    }
};

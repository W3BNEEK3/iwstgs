<?php

use Database\Seeders\GuideContentSeeder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The learner-aware guide (design doc v2-05). Tiroco now queues messages of
 * its own — nudges from what it observed, tips, outside resources and
 * feature announcements — on top of the authored page walkthroughs.
 *
 * Also adds platform_settings (admin-editable key/value settings), and the
 * two feature flags the guide runs behind. Flags and the starter tips and
 * resources are inserted here rather than only in seeders, so an existing
 * install gets a working guide from `php artisan migrate` alone; both inserts
 * leave rows an admin already changed untouched.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('user_guide_preferences', function (Blueprint $table) {
            $table->json('muted_kinds')->nullable();
            $table->json('paused_until')->nullable();
            $table->timestamp('last_active_at')->nullable();
        });

        Schema::create('guide_tips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 60)->unique();
            $table->string('area', 40);
            $table->string('text', 300);
            $table->json('pages')->nullable(); // guide page keys it suits; null = anywhere
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('guide_resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 60)->unique();
            $table->string('name', 120);
            $table->string('url', 500);
            $table->string('kind', 20); // reference | practice | course | tool | community
            $table->json('dimensions');  // competence dimension ids it helps with
            $table->string('level', 20)->default('beginner');
            $table->boolean('is_free')->default(true);
            $table->string('blurb', 300);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_checked_at')->nullable();
            $table->boolean('last_check_ok')->nullable();
            $table->timestamps();
        });

        Schema::create('guide_resource_opt_outs', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('resource_id');
            $table->string('reason', 20); // already_use | not_for_me
            $table->timestamp('created_at')->useCurrent();

            $table->primary(['user_id', 'resource_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('resource_id')->references('id')->on('guide_resources')->cascadeOnDelete();
        });

        Schema::create('feature_announcements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('internal_title', 150);
            $table->text('notes');
            $table->string('title', 80)->nullable();
            $table->string('body', 400)->nullable();
            $table->string('link_url', 300)->nullable(); // internal path, e.g. /learn/profile
            $table->string('feature_flag', 100)->nullable();
            $table->string('status', 20)->default('draft'); // draft | published | archived
            $table->timestamp('published_at')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('guide_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('kind', 20);          // nudge | tip | resource | announcement
            $table->string('trigger_key', 40);
            $table->string('context_ref', 100)->nullable(); // e.g. task id; scopes cooldowns
            $table->json('facts')->nullable();   // what the trigger saw, for the AI writer
            $table->string('title', 120);
            $table->text('body');
            $table->string('cta_label', 60)->nullable();
            $table->string('cta_url', 500)->nullable();
            $table->uuid('tip_id')->nullable();
            $table->uuid('resource_id')->nullable();
            $table->uuid('announcement_id')->nullable();
            $table->string('status', 20)->default('pending'); // pending | ready | shown | dismissed | expired
            $table->string('generated_by', 20)->default('authored'); // authored | ai | fallback
            $table->string('rating', 20)->nullable(); // helpful | not_helpful
            $table->timestamp('shown_at')->nullable();
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'trigger_key', 'context_ref']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->string('key', 100)->primary();
            $table->json('value');
            $table->timestamps();
        });

        foreach ([
            ['flag_key' => 'guide.ai_nudges',     'description' => 'Tiroco watches how each learner is doing and queues nudges, tips and resource suggestions (AI-written when a provider is configured).'],
            ['flag_key' => 'guide.announcements', 'description' => 'Learner feature announcements from Tiroco and the What\'s new page.'],
        ] as $flag) {
            DB::table('feature_flags')->insertOrIgnore($flag + [
                'module' => 'Guidance', 'is_enabled' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        (new GuideContentSeeder())->run();
    }

    public function down(): void
    {
        DB::table('feature_flags')->whereIn('flag_key', ['guide.ai_nudges', 'guide.announcements'])->delete();
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('guide_messages');
        Schema::dropIfExists('feature_announcements');
        Schema::dropIfExists('guide_resource_opt_outs');
        Schema::dropIfExists('guide_resources');
        Schema::dropIfExists('guide_tips');

        Schema::table('user_guide_preferences', function (Blueprint $table) {
            $table->dropColumn(['muted_kinds', 'paused_until', 'last_active_at']);
        });
    }
};

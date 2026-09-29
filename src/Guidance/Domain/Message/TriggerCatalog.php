<?php
namespace Src\Guidance\Domain\Message;

/**
 * Every situation in which Tiroco speaks up unprompted (design doc v2-05 §3).
 * Rules decide *when*; the AI (or the authored fallback) decides *what*.
 *
 * `cooldown_hours` is per learner, scoped to the message's context_ref when it
 * has one (a task, a dimension, a rank event), so "once per task" and "once a
 * week" are the same mechanism. `why` is shown behind "Why am I seeing this?".
 * Admins can switch triggers off and change cooldowns (platform setting
 * `guide.triggers`); these are the defaults.
 */
final class TriggerCatalog
{
    public const REPEAT_FAIL = 'repeat-fail';
    public const STUCK_IDLE = 'stuck-idle';
    public const THIN_EXPLANATIONS = 'thin-explanations';
    public const RANK_CHANGE = 'rank-change';
    public const WELCOME_BACK = 'welcome-back';
    public const WEAK_DIMENSION = 'weak-dimension';
    public const QUIET_MOMENT = 'quiet-moment';
    public const ANNOUNCEMENT = 'announcement';

    /** @return array<string, array{kind: GuideKind, label: string, cooldown_hours: int, why: string}> */
    public static function all(): array
    {
        return [
            self::REPEAT_FAIL => [
                'kind' => GuideKind::Nudge, 'label' => 'Second failed attempt on a task', 'cooldown_hours' => 72,
                'why' => 'You have tried this task more than once without a pass yet.',
            ],
            self::STUCK_IDLE => [
                'kind' => GuideKind::Nudge, 'label' => 'On a task for 25+ minutes without submitting', 'cooldown_hours' => 24,
                'why' => 'You have been on this task for a while without submitting.',
            ],
            self::THIN_EXPLANATIONS => [
                'kind' => GuideKind::Nudge, 'label' => 'Short explanations and low communication scores', 'cooldown_hours' => 168,
                'why' => 'Your recent explanations were short and communication scored below proficient.',
            ],
            self::RANK_CHANGE => [
                'kind' => GuideKind::Nudge, 'label' => 'Rank moved up or down', 'cooldown_hours' => 0,
                'why' => 'Your rank just changed.',
            ],
            self::WELCOME_BACK => [
                'kind' => GuideKind::Nudge, 'label' => 'Back after 5+ days away', 'cooldown_hours' => 72,
                'why' => 'You have been away for a few days.',
            ],
            self::WEAK_DIMENSION => [
                'kind' => GuideKind::Resource, 'label' => 'Same skill below proficient in 3 of the last 5 results', 'cooldown_hours' => 336,
                'why' => 'One skill has scored below proficient in several recent results.',
            ],
            self::QUIET_MOMENT => [
                'kind' => GuideKind::Tip, 'label' => 'A calm moment (after a pass, or on the projects or profile page)', 'cooldown_hours' => 24,
                'why' => 'A general tip, picked for where you are in the platform.',
            ],
            self::ANNOUNCEMENT => [
                'kind' => GuideKind::Announcement, 'label' => 'A new learner feature was published', 'cooldown_hours' => 0,
                'why' => 'Something new was added for learners.',
            ],
        ];
    }

    /** @return array{kind: GuideKind, label: string, cooldown_hours: int, why: string}|null */
    public static function find(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    /** Tie-break between messages of the same kind queued together: catalogue order, most specific first. */
    public static function rank(string $key): int
    {
        $index = array_search($key, array_keys(self::all()), true);

        return $index === false ? PHP_INT_MAX : $index;
    }
}

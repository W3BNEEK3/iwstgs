<?php
namespace Src\Guidance\Domain\Message;

/** The four kinds of message Tiroco queues on its own (walkthroughs are separate: GuideCatalog). */
enum GuideKind: string
{
    case Nudge = 'nudge';
    case Tip = 'tip';
    case Resource = 'resource';
    case Announcement = 'announcement';

    /** Order in which due messages are delivered, most urgent first. */
    public function priority(): int
    {
        return match ($this) {
            self::Announcement => 0,
            self::Nudge => 1,
            self::Resource => 2,
            self::Tip => 3,
        };
    }

    /** Unsolicited kinds count toward the daily cap; announcements don't. */
    public function isCapped(): bool
    {
        return $this !== self::Announcement;
    }

    /** Label for the learner's per-kind switches. */
    public function switchLabel(): string
    {
        return match ($this) {
            self::Nudge => 'Hints when I seem stuck',
            self::Tip => 'Tips on using Areyna well',
            self::Resource => 'Suggestions of outside learning resources',
            self::Announcement => 'New feature announcements',
        };
    }
}

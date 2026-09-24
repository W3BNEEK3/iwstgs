<?php

namespace Src\Shared\Domain\Enum;

/**
 * Canonical availability states for content objects.
 *
 * Note: these map to the logical state derived from (is_published, is_active)
 * column combinations — they are NOT stored directly as a column value.
 * The database stores the two boolean flags; this enum expresses the
 * computed result for use in application layer logic and UI rendering.
 */
enum AvailabilityStatus: string
{
    case Published = 'published';
    case Draft     = 'draft';
    case Archived  = 'archived';
}

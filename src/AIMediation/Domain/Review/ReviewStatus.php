<?php
namespace Src\AIMediation\Domain\Review;

enum ReviewStatus: string
{
    case Pending  = 'pending';
    case InReview = 'in_review';
    case Resolved = 'resolved';
}

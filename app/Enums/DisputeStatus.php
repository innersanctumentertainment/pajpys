<?php

namespace App\Enums;

enum DisputeStatus: string
{
    case Open = 'open';
    case UnderReview = 'under_review';
    case ResolvedClient = 'resolved_client';
    case ResolvedVa = 'resolved_va';
    case ResolvedSplit = 'resolved_split';
    case Closed = 'closed';
}

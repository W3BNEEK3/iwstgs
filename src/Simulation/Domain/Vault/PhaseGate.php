<?php

namespace Src\Simulation\Domain\Vault;

enum PhaseGate: string
{
    case PRE_INDUCTION = 'pre_induction';
    case POST_INDUCTION = 'post_induction';
    case POST_SPRINT_1 = 'post_sprint_1';
    case MID_SESSION = 'mid_session';
    case ADVANCED_ONLY = 'advanced_only';
}

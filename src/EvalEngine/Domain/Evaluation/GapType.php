<?php
namespace Src\EvalEngine\Domain\Evaluation;

enum GapType: string
{
    case KnowledgeGap = 'knowledge_gap';
    case StrategyGap  = 'strategy_gap';
}

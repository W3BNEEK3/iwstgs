<?php
namespace Src\Simulation\Domain\Scenario;

enum MaterialType: string
{
    case Document     = 'document';
    case Email        = 'email';
    case SlackMessage = 'slack_message';
    case Ticket       = 'ticket';
    case Report       = 'report';
    case Notes        = 'notes';
}

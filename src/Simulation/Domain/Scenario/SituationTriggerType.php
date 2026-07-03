<?php
namespace Src\Simulation\Domain\Scenario;

enum SituationTriggerType: string
{
    case SlackMessage   = 'slack_message';
    case Email          = 'email';
    case MeetingSummary = 'meeting_summary';
    case IncidentReport = 'incident_report';
    case Ticket         = 'ticket';
    case HandoverNote   = 'handover_note';
}

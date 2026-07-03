@props(['type', 'title', 'content'])

<article class="ref-material ref-material--{{ $type }}">
    @switch($type)
        @case('email')
            <header class="rm-email-header">✉️ <strong>{{ $title }}</strong></header>
            @break
        @case('slack_message')
            <header class="rm-slack-header"># <strong>{{ $title }}</strong></header>
            @break
        @case('ticket')
            <header class="rm-ticket-header">🎫 <strong>{{ $title }}</strong></header>
            @break
        @case('incident_report')
        @case('report')
            <header class="rm-report-header">📄 <strong>{{ $title }}</strong></header>
            @break
        @default
            <header class="rm-doc-header">📝 <strong>{{ $title }}</strong></header>
    @endswitch

    <div class="rm-body">{!! nl2br(e($content)) !!}</div>
</article>

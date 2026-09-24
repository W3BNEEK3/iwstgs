{{-- In-universe content — anything a person WITHIN the simulated workplace wrote
     (a Slack message from the tech lead, a ticket, a sprint goal), never rendered
     as plain page copy so learners can tell "this is part of the simulation, read
     it" apart from ordinary system/UI text. Distinguished by background + left
     accent border + icon + an explicit label — never color alone (design doc §1
     rule 4). Resolves design doc §16's previously-open "situation-trigger panel"
     decision; the same treatment now covers situation triggers, sprint goals, and
     reference materials uniformly, wherever they appear.

     <x-content.narrative-panel icon="chat" label="Slack message from Tech Lead">
         Can you take the queue-enqueue endpoint? Ticket MQ-14.
     </x-content.narrative-panel> --}}
@props(['icon' => 'chat', 'label'])

<div {{ $attributes->merge(['class' => 'narrative-panel']) }}>
    <div class="narrative-panel-icon">
        <x-ui.icon :name="$icon" :size="18" />
    </div>
    <div class="narrative-panel-body">
        <div class="narrative-panel-label">{{ $label }}</div>
        <div class="narrative-panel-content">{{ $slot }}</div>
    </div>
</div>

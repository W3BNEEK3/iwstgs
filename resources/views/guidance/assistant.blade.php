{{-- In-app guide, voiced by Tiroco. Two things share this card:
     - walkthrough steps for this page (authored), auto-opening once each when
       the guide is on; the lightbulb replays them;
     - Tiroco's own messages (nudges, tips, resources, announcements), fetched
       from guide.next after the page has loaded, one per page at most. --}}
@php
    $config = [
        'steps'      => $guide?->steps ?? [],
        'autoShow'   => $guide?->autoShowKeys ?? [],
        'dismissUrl' => route('guide.dismiss', ['step' => '__STEP__']),
        'disableUrl' => route('guide.disable'),
        'nextUrl'    => $messagesOn ? route('guide.next', array_filter(['page' => $page])) : null,
        'messageUrl' => route('guide.messages.dismiss', ['message' => '00000000-0000-0000-0000-000000000000']),
        'stuck'      => $stuckWatch(),
    ];
@endphp

<div class="guide-root" x-data="guide(@js($config))" data-auto-show="{{ implode(',', $config['autoShow']) }}" @keydown.escape.window="open && later()">
    @if ($config['steps'] !== [])
        <button type="button" class="guide-launcher" x-show="!open" x-cloak @click="replay()" aria-label="Open the guide for this page" title="Tips for this page">
            <x-ui.icon name="lightbulb" :size="20" />
        </button>
    @endif

    <section class="guide-card" x-show="open" x-cloak x-transition.opacity.duration.150ms role="dialog" aria-labelledby="guide-title" aria-describedby="guide-body" :class="message && 'guide-card--' + message.kind">
        <header class="guide-head">
            <span class="guide-avatar" aria-hidden="true"><x-ui.icon name="auto_awesome" :size="18" /></span>
            <span class="guide-meta">
                <span class="guide-name" x-text="message ? 'Tiroco · ' + kindLabel() : 'Tiroco · your guide'"></span>
                <strong id="guide-title" class="guide-title" x-text="message ? message.title : step()?.title"></strong>
            </span>
            <button type="button" class="btn-icon guide-close" @click="later()" aria-label="Close">
                <x-ui.icon name="close" :size="18" />
            </button>
        </header>

        {{-- Walkthrough steps --}}
        <template x-if="!message && !confirmingOff">
            <div>
                <div class="guide-body" id="guide-body">
                    <h4 x-text="currentCard()?.[0]"></h4>
                    <p x-text="currentCard()?.[1]"></p>
                </div>

                <div class="guide-dots" x-show="(step()?.cards.length ?? 0) > 1" aria-hidden="true">
                    <template x-for="(c, i) in step()?.cards ?? []" :key="i">
                        <span class="guide-dot" :class="{ 'is-active': i === card }"></span>
                    </template>
                </div>

                <footer class="guide-foot">
                    <button type="button" class="guide-off" @click="confirmingOff = true">Turn off guide</button>
                    <span class="spacer"></span>
                    <button type="button" class="btn btn-secondary" x-show="card > 0" @click="card--">Back</button>
                    <button type="button" class="btn btn-primary" x-ref="primary" @click="next()" x-text="isLastCard() ? 'Got it' : 'Next'"></button>
                </footer>
            </div>
        </template>

        {{-- One of Tiroco's own messages --}}
        <template x-if="message && !confirmingOff">
            <div>
                <div class="guide-body" id="guide-body">
                    <p x-text="message.body"></p>
                </div>

                <a class="btn btn-secondary guide-cta" x-show="message.ctaUrl" :href="message.ctaUrl"
                   :target="message.ctaIsExternal ? '_blank' : null" :rel="message.ctaIsExternal ? 'noopener noreferrer' : null"
                   @click="done()">
                    <span x-text="message.ctaLabel"></span>
                    <x-ui.icon name="open_in_new" :size="14" x-show="message.ctaIsExternal" />
                </a>

                <div class="guide-optout" x-show="message.canOptOutOfResource">
                    <button type="button" class="guide-off" @click="optOut('already_use')">I already use it</button>
                    <button type="button" class="guide-off" @click="optOut('not_for_me')">Not for me</button>
                </div>

                <p class="guide-why" x-show="showWhy" x-text="message.why"></p>

                <footer class="guide-foot">
                    <span class="guide-rate" x-show="rated === null">
                        <span>Helpful?</span>
                        <button type="button" class="btn-icon" @click="rate(true)" aria-label="Yes, helpful"><x-ui.icon name="thumb_up" :size="16" /></button>
                        <button type="button" class="btn-icon" @click="rate(false)" aria-label="Not helpful"><x-ui.icon name="thumb_down" :size="16" /></button>
                    </span>
                    <span class="guide-rate" x-show="rated !== null" x-cloak>Thanks!</span>
                    <button type="button" class="guide-off" @click="showWhy = !showWhy" x-text="showWhy ? 'Hide' : 'Why this?'"></button>
                    <span class="spacer"></span>
                    <button type="button" class="btn btn-primary" x-ref="primary" @click="done()">Got it</button>
                </footer>
                <p class="guide-settings-link">
                    <a href="{{ route('learn.settings') }}#guide">Choose what Tiroco tells you</a>
                </p>
            </div>
        </template>

        <template x-if="confirmingOff">
            <div>
                <div class="guide-body">
                    <h4>Turn off all tips?</h4>
                    <p>I'll stop popping up. You can still tap the lightbulb for tips on any page, and switch me back on in Settings.</p>
                </div>
                <footer class="guide-foot">
                    <span class="spacer"></span>
                    <button type="button" class="btn btn-secondary" @click="confirmingOff = false">Keep tips</button>
                    <button type="button" class="btn btn-primary" @click="turnOff()">Turn off</button>
                </footer>
            </div>
        </template>
    </section>
</div>

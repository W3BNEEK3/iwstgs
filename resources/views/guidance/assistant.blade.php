{{-- In-app guide, voiced by Tiroco. Auto-opens for steps the learner hasn't
     dismissed (when the guide is on); the launcher replays this page's tips. --}}
@php
    $config = [
        'steps'      => $guide->steps,
        'autoShow'   => $guide->autoShowKeys,
        'dismissUrl' => route('guide.dismiss', ['step' => '__STEP__']),
        'disableUrl' => route('guide.disable'),
    ];
@endphp

<div class="guide-root" x-data="guide(@js($config))" data-auto-show="{{ implode(',', $guide->autoShowKeys) }}" @keydown.escape.window="open && later()">
    <button type="button" class="guide-launcher" x-show="!open" x-cloak @click="replay()" aria-label="Open the guide for this page" title="Tips for this page">
        <x-ui.icon name="lightbulb" :size="20" />
    </button>

    <section class="guide-card" x-show="open" x-cloak x-transition.opacity.duration.150ms role="dialog" aria-labelledby="guide-title" aria-describedby="guide-body">
        <header class="guide-head">
            <span class="guide-avatar" aria-hidden="true"><x-ui.icon name="auto_awesome" :size="18" /></span>
            <span class="guide-meta">
                <span class="guide-name">Tiroco &middot; your guide</span>
                <strong id="guide-title" class="guide-title" x-text="step()?.title"></strong>
            </span>
            <button type="button" class="btn-icon guide-close" @click="later()" aria-label="Close for now">
                <x-ui.icon name="close" :size="18" />
            </button>
        </header>

        <template x-if="!confirmingOff">
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

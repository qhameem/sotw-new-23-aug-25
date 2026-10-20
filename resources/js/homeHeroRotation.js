export default function homeHeroRotation(config) {
    return {
        phrases: config.phrases,
        drafts: [...config.drafts],
        editing: config.editing,
        paused: false,
        index: 0,
        text: config.phrases[0],
        phase: 'holding',
        timer: null,
        motionQuery: null,
        motionListener: null,
        init() {
            this.motionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.motionListener = () => this.restart();
            this.motionQuery.addEventListener('change', this.motionListener);
            this.restart();
        },
        restart() {
            clearTimeout(this.timer);
            this.timer = null;
            this.index = 0;
            this.text = this.phrases[0];
            this.phase = 'holding';
            if (this.motionQuery.matches || this.phrases.length < 2) return;
            this.schedule(2200);
        },
        schedule(delay) {
            this.timer = setTimeout(() => this.tick(), delay);
        },
        tick() {
            if (this.editing || this.paused || document.hidden) {
                this.schedule(150);
                return;
            }
            if (this.phase === 'holding') this.phase = 'deleting';
            if (this.phase === 'deleting') {
                this.text = Array.from(this.text).slice(0, -1).join('');
                if (!this.text) {
                    this.index = (this.index + 1) % this.phrases.length;
                    this.phase = 'typing';
                    this.schedule(300);
                } else {
                    this.schedule(40);
                }
                return;
            }
            const characters = Array.from(this.phrases[this.index]);
            this.text = characters.slice(0, Array.from(this.text).length + 1).join('');
            if (this.text === this.phrases[this.index]) {
                this.phase = 'holding';
                this.schedule(2200);
            } else {
                this.schedule(75);
            }
        },
        move(index, offset) {
            const target = index + offset;
            if (target < 0 || target >= this.drafts.length) return;
            [this.drafts[index], this.drafts[target]] = [this.drafts[target], this.drafts[index]];
        },
        cancel() {
            this.editing = false;
            this.$refs.editor.reset();
            this.drafts = [...this.phrases];
        },
        destroy() {
            clearTimeout(this.timer);
            this.motionQuery?.removeEventListener('change', this.motionListener);
        },
    };
}

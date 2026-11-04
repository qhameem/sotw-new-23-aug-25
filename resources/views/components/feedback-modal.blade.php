<x-modal name="site-feedback" maxWidth="lg" viewportPadding="compact" focusable>
    <div role="dialog" aria-modal="true" aria-labelledby="feedback-title" class="p-6"
        x-data="{
            sending: false, sent: false, error: '',
            async submit() {
                if (this.sending) return;
                this.sending = true;
                this.error = '';
                try {
                    const response = await fetch(@js(route('feedback.store')), {
                        method: 'POST',
                        headers: { 'Accept': 'application/json' },
                        body: new FormData(this.$refs.form),
                    });
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        this.error = response.status === 429
                            ? 'Too many attempts. Please try again in a minute.'
                            : (response.status === 422 ? Object.values(data.errors || {}).flat().join(' ') : 'Unable to send feedback. Please try again.');
                        return;
                    }
                    this.sent = true;
                    this.$refs.form.reset();
                } catch (error) {
                    this.error = 'Unable to send feedback. Please try again.';
                } finally {
                    this.sending = false;
                }
            }
        }"
        @open-modal.window="if ($event.detail.name === 'site-feedback' && !sending) { sent = false; error = ''; }">
        <div class="mb-5 flex items-center justify-between gap-4">
            <h2 id="feedback-title" class="text-xl font-semibold text-gray-900">Share feedback</h2>
            <button type="button" @click="$dispatch('close-modal', 'site-feedback')" class="rounded p-2 text-gray-600 hover:bg-gray-100" aria-label="Close feedback dialog">✕</button>
        </div>
        <p x-show="sent" x-cloak role="status" class="text-sm text-gray-700">Your feedback has been sent.</p>
        <form x-ref="form" x-show="!sent" @submit.prevent="submit" class="space-y-4">
            @csrf
            <div>
                <label for="feedback-type" class="block text-sm font-medium text-gray-700">Feedback type</label>
                <select id="feedback-type" name="type" required class="mt-1 w-full rounded-md border-gray-300 text-gray-900">
                    <option value="suggestion">Suggestion</option>
                    <option value="bug">Report a problem</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div>
                <label for="feedback-message" class="block text-sm font-medium text-gray-700">Message</label>
                <textarea id="feedback-message" name="message" rows="4" required minlength="10" maxlength="5000" class="mt-1 w-full rounded-md border-gray-300 text-gray-900" placeholder="What could we improve?"></textarea>
            </div>
            <div>
                <label for="feedback-email" class="block text-sm font-medium text-gray-700">Email (optional, for a reply)</label>
                <input id="feedback-email" name="email" type="email" maxlength="254" class="mt-1 w-full rounded-md border-gray-300 text-gray-900" autocomplete="email">
            </div>
            <div class="hidden" aria-hidden="true">
                <label for="feedback-website">Website</label>
                <input id="feedback-website" name="website" tabindex="-1" autocomplete="off">
            </div>
            <p x-show="error" x-text="error" role="alert" class="text-sm text-red-600"></p>
            <button type="submit" :disabled="sending" class="rounded-md bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-700 disabled:opacity-50" x-text="sending ? 'Sending…' : 'Send feedback'">Send feedback</button>
        </form>
    </div>
</x-modal>

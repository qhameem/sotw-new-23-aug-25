<div class="space-y-6">
    <h2 class="text-2xl font-semibold">Footer badges</h2>
    <p class="text-sm text-[var(--lr-muted)]">Add badge embed codes from other sites. Save changes to publish additions, edits, or deletions.</p>
    <form method="POST" action="{{ route('launch-readiness.settings.badges.update', ['toolSlug' => $toolSlug]) }}"
        class="space-y-6" x-data="{ badges: @js(array_values(old('badges', $toolFooterBadges))) }">
        @csrf
        @method('PATCH')
        @if($errors->any())
            <ul class="list-inside list-disc text-sm text-red-600">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <template x-for="(badge, index) in badges" :key="index">
            <fieldset class="space-y-3 rounded-xl border p-4" style="border-color: var(--lr-border);">
                <label class="block text-sm font-semibold">
                    Badge name
                    <input type="text" :name="`badges[${index}][name]`" x-model="badge.name" required maxlength="120"
                        class="mt-2 block w-full rounded-xl border px-3 py-2" style="background: var(--lr-panel-strong); border-color: var(--lr-border); color: var(--lr-text);">
                </label>
                <label class="block text-sm font-semibold">
                    Embed code
                    <textarea :name="`badges[${index}][code]`" x-model="badge.code" required maxlength="20000" rows="5" spellcheck="false"
                        class="mt-2 block w-full rounded-xl border px-3 py-2 font-mono text-sm" style="background: var(--lr-panel-strong); border-color: var(--lr-border); color: var(--lr-text);"></textarea>
                </label>
                <button type="button" @click="badges.splice(index, 1)" class="text-sm font-medium text-red-600">Delete badge</button>
            </fieldset>
        </template>
        <div class="flex flex-wrap gap-4">
            <button type="button" @click="badges.push({ name: '', code: '' })" :disabled="badges.length >= 30" class="rounded-xl border px-4 py-2 text-sm font-semibold disabled:opacity-50">Add badge</button>
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save badges</button>
        </div>
    </form>
</div>

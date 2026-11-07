<div class="mb-4 rounded border p-4" x-data="{
    active: false, key: '', label: '', size: 16, weight: '400', color: '#111827', align: 'left', leading: 1.5,
    select(event) {
        if (!this.active) return;
        const block = event.target.closest('[data-page-style-key]');
        if (!block || !block.closest('article')) return;
        event.preventDefault();
        const style = getComputedStyle(block);
        this.key = block.dataset.pageStyleKey;
        this.label = block.textContent.trim().slice(0, 70);
        this.size = Math.round(parseFloat(style.fontSize));
        this.weight = style.fontWeight === 'bold' ? '700' : style.fontWeight;
        const rgb = style.color.match(/\d+/g);
        this.color = rgb ? '#' + rgb.slice(0, 3).map(v => Number(v).toString(16).padStart(2, '0')).join('') : '#111827';
        this.align = ['left', 'center', 'right', 'justify'].includes(style.textAlign) ? style.textAlign : 'left';
        this.leading = style.lineHeight === 'normal' ? 1.5 : Math.min(3, Math.max(1, Number((parseFloat(style.lineHeight) / parseFloat(style.fontSize)).toFixed(2))));
    }
}" @click.window="select($event)">
    <button type="button" class="rounded border px-3 py-2" @click="active = !active; key = ''" x-text="active ? 'Exit formatting mode' : 'Edit text formatting'"></button>
    <p x-show="active" x-cloak>Click a heading, paragraph, list item, or table cell to format it.</p>
    @if(session('success')) <p role="status">{{ session('success') }}</p> @endif
    @if($errors->any()) <p role="alert">{{ $errors->first() }}</p> @endif
    <form x-show="active && key" x-cloak method="POST" action="{{ route('admin.pages.text-style', $page) }}" class="mt-3 flex flex-wrap items-end gap-3">
        @csrf
        @method('PATCH')
        <input type="hidden" name="key" :value="key">
        <p class="w-full" x-text="label"></p>
        <label>Size (px)<input class="block w-20 rounded border" type="number" name="size" min="10" max="96" x-model="size" required></label>
        <label>Weight<select class="block rounded border" name="weight" x-model="weight">
            @foreach([300,400,500,600,700,800,900] as $weight)<option value="{{ $weight }}">{{ $weight }}</option>@endforeach
        </select></label>
        <label>Color<input class="block" type="color" name="color" x-model="color"></label>
        <label>Alignment<select class="block rounded border" name="align" x-model="align">
            @foreach(['left','center','right','justify'] as $align)<option value="{{ $align }}">{{ ucfirst($align) }}</option>@endforeach
        </select></label>
        <label>Line height<input class="block w-20 rounded border" type="number" name="leading" min="1" max="3" step="0.01" x-model="leading" required></label>
        <button class="rounded border px-3 py-2" type="submit">Save formatting</button>
        <button class="rounded border px-3 py-2" type="submit" name="reset" value="1">Reset formatting</button>
    </form>
</div>

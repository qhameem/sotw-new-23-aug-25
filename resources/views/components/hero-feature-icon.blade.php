@props(['index', 'path' => null, 'defaultPath', 'editable' => false])
<div class="shrink-0" @if($editable) x-data="{ uploading: false }" @endif>
    @if($editable)
        <form action="{{ route('admin.home-hero.features.icon.update', ['feature' => $index]) }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="file" name="icon" accept="image/png,image/jpeg,image/webp" x-ref="icon" class="sr-only" tabindex="-1" aria-label="Upload feature icon {{ $index + 1 }}" @change="if ($el.files.length) { uploading = true; $el.form.requestSubmit(); }">
            <button type="button" @click="$refs.icon.click()" :disabled="uploading" :aria-busy="uploading" aria-label="Upload feature icon {{ $index + 1 }}" title="Upload icon (PNG, JPG, WebP; max 2 MB)" class="block rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-primary-500 disabled:opacity-40">
    @endif
    @if($path)
        <span class="block h-4 w-4" aria-hidden="true" style="background-color: var(--color-primary-500); mask: url('{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}') center / contain no-repeat; -webkit-mask: url('{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}') center / contain no-repeat;"></span>
    @else
        <svg class="h-4 w-4 text-primary-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $defaultPath }}" /></svg>
    @endif
    @if($editable)
            </button>
        </form>
        @foreach($errors->getBag('homeHeroIcon'.$index)->all() as $error)
            <p role="alert" class="mt-2 max-w-xs text-xs text-red-600">{{ $error }}</p>
        @endforeach
        @if(session('home_hero_icon_saved') === $index)
            <x-admin-save-message class="sr-only">Feature icon saved.</x-admin-save-message>
        @endif
    @endif
</div>

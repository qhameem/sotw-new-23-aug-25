@if(auth()->user()?->hasRole('admin') && isset($sidebarPage))
    <form method="POST" action="{{ route('admin.pages.sidebar-section', $sidebarPage) }}" class="mb-2 flex justify-end">
        @csrf
        @method('PATCH')
        <input type="hidden" name="section" value="{{ $sectionKey }}">
        <input type="hidden" name="enabled" value="{{ $sectionEnabled ? '0' : '1' }}">
        <button type="submit" class="rounded border px-3 py-1 text-sm"
            aria-label="{{ $sectionEnabled ? 'Remove' : 'Add' }} {{ \App\Models\CustomPage::SIDEBAR_SECTIONS[$sectionKey] }}">
            {{ $sectionEnabled ? 'Remove' : 'Add' }} {{ \App\Models\CustomPage::SIDEBAR_SECTIONS[$sectionKey] }}
        </button>
    </form>
@endif

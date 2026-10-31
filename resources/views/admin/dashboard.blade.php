<section class="admin-dashboard-section">
    <div class="admin-section-heading"><h2>Catalog & administration</h2><p>Manage products, review submissions, and configure the site.</p></div>
    <div class="admin-shortcut-grid">
        @foreach(config('admin-navigation.primary') as $item)
            @continue($item['route'] === 'admin.dashboard')
            <a href="{{ route($item['route']) }}" class="admin-shortcut"><span class="admin-shortcut-icon"><x-admin.approval-icon :name="$item['icon']" /></span><span>{{ $item['label'] }}</span><x-admin.approval-icon name="arrow" width="18" height="18" /></a>
        @endforeach
    </div>
</section>
<section class="admin-dashboard-section">
    <div class="admin-section-heading"><h2>More admin tools</h2><p>Content, promotion, search, and system tools.</p></div>
    <div class="admin-shortcut-grid">
        @foreach(config('admin-navigation.additional') as $item)
            <a href="{{ route($item['route']) }}" class="admin-shortcut"><span class="admin-shortcut-icon"><x-admin.approval-icon :name="$item['icon']" /></span><span>{{ $item['label'] }}</span><x-admin.approval-icon name="arrow" width="18" height="18" /></a>
        @endforeach
    </div>
</section>

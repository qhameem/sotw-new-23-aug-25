<aside class="approval-sidebar" aria-label="Admin navigation">
    <a href="{{ url('/') }}" class="approval-brand"><span class="approval-brand-mark"><x-admin.approval-icon /></span><span>Software on the web</span></a>
    <nav class="approval-navigation">
        @foreach(config('admin-navigation.primary') as $item)
            <a href="{{ route($item['route']) }}" @if($activeAdminRoute === $item['route']) aria-current="page" @endif><x-admin.approval-icon :name="$item['icon']" /><span>{{ $item['label'] }}</span></a>
        @endforeach
    </nav>
    <form method="POST" action="{{ route('logout') }}" class="approval-logout">@csrf<button type="submit"><x-admin.approval-icon name="logout" /><span>Log out</span></button></form>
</aside>

<div class="space-y-6">
    @if($pageSidebarSections['partners'])
        @include('site.partials.sidebar-section-control', ['sidebarPage' => $page, 'sectionKey' => 'partners', 'sectionEnabled' => true])
        @include('partials._sidebar-ads')
    @endif
    @if($pageSidebarSections['top_categories'])
        @include('site.partials.sidebar-section-control', ['sidebarPage' => $page, 'sectionKey' => 'top_categories', 'sectionEnabled' => true])
        <x-top-categories />
    @endif
    @if($pageSidebarSections['newsletter'])
        @include('site.partials.sidebar-section-control', ['sidebarPage' => $page, 'sectionKey' => 'newsletter', 'sectionEnabled' => true])
        <div class="mt-6 hidden md:block">
            @include('partials._newsletter-signup-form', ['placement' => 'sidebar'])
        </div>
    @endif
</div>

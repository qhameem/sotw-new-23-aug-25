<div class="space-y-6">
    @if($pageSidebarSections['partners'])
        @include('partials._sidebar-ads')
    @endif
    @if($pageSidebarSections['top_categories'])
        <x-top-categories />
    @endif
    @if($pageSidebarSections['newsletter'])
        <div class="mt-6 hidden md:block">
            @include('partials._newsletter-signup-form', ['placement' => 'sidebar'])
        </div>
    @endif
</div>

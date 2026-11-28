<nav aria-label="Breadcrumb" class="mb-5 text-xs text-neutral-600">
    <ol class="flex flex-wrap items-center gap-1">
        <li>
            <a href="{{ route('launch-readiness.index', ['toolSlug' => $toolSlug]) }}"
                class="underline underline-offset-4 hover:text-neutral-950">Launch Readiness Checker</a>
        </li>
        <li class="flex items-center gap-1">
            <span aria-hidden="true">/</span>
            <span aria-current="page" class="font-medium text-neutral-800">{{ $currentPageLabel }}</span>
        </li>
    </ol>
</nav>

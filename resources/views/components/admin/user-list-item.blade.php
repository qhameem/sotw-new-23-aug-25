@props(['user'])

<article class="p-4 flex items-center gap-3 md:gap-3 transition relative group">
    <div class="flex-1">
        <h2 class="text-sm font-semibold leading-tight mb-0.5 flex items-center">
            <a href="{{ route('admin.users.show', $user) }}" class="text-left text-blue-600 hover:underline">{{ $user->name }}</a>
        </h2>
        <p class="text-gray-800 text-sm mt-0.5 line-clamp-2">{{ $user->email }}</p>
        <div class="mt-2 flex items-center gap-4 text-xs text-gray-500">
            <span>Joined: {{ $user->created_at->format('M d, Y') }}</span>
        </div>
    </div>
</article>
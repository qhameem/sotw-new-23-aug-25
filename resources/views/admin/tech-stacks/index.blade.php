@extends('layouts.app')

@section('header-title')
    Tech Stacks
@endsection

@section('actions')
    <a href="{{ route('admin.tech-stacks.create') }}"
       class="inline-flex items-center px-4 py-2 bg-primary-500 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-primary-600 focus:bg-primary-600 active:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150"
       style="color: var(--color-primary-button-text);">Create Tech Stack</a>
@endsection

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <div>
        {{-- Session Messages --}}
        @if(session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('status') }}</span>
        </div>
        @endif

        {{-- Tech Stacks Section --}}
        <div>
            <div class="bg-white shadow-sm sm:rounded-lg overflow-x-auto">
                <table class="min-w-full bg-white rounded">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                            <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
                            <th class="py-2 px-4 border-b text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($techStacks as $techStack)
                        <tr>
                            <td class="py-2 px-4 border-b text-sm text-gray-900">{{ $techStack->id }}</td>
                            <td class="py-2 px-4 border-b text-sm font-medium text-gray-900">
                                <span class="mr-2">{{ $techStack->name }}</span>
                                @if($techStack->icon)
                                    <i class="{{ $techStack->icon }}"></i>
                                @endif
                            </td>
                            <td class="py-2 px-4 border-b text-sm text-gray-700">{{ $techStack->slug }}</td>
                            <td class="py-2 px-4 border-b text-sm text-gray-700">
                                @if($techStack->icon)
                                    <i class="{{ $techStack->icon }}"></i> {{ $techStack->icon }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="py-2 px-4 border-b text-sm">
                                <a href="{{ route('admin.tech-stacks.edit', $techStack) }}"
                                   class="text-primary-600 hover:text-primary-900 mr-2">Edit</a>
                                <form action="{{ route('admin.tech-stacks.destroy', $techStack) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900"
                                            onclick="return confirm('Are you sure you want to delete this tech stack?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="py-4 px-4 text-center text-sm text-gray-500">No tech stacks found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($techStacks->hasPages())
            <div class="mt-4">
                {{ $techStacks->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
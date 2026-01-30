@extends('layouts.admin')

@section('title', 'Manage Tech Stacks')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="techStackManager()">
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Manage Tech Stacks</h1>
        <p class="mt-2 text-sm text-gray-600">Create, edit, or delete tech stacks used in products</p>
    </div>

    @if(session('status'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
            {{ session('status') }}
        </div>
    @endif

    <div class="bg-white shadow rounded-lg p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Add New Tech Stack</h2>
        <form method="POST" action="{{ route('admin.tech-stacks.store') }}">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="icon" class="block text-sm font-medium text-gray-700 mb-1">Icon (Optional)</label>
                    <input type="text" name="icon" id="icon" value="{{ old('icon') }}"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                           placeholder="Font Awesome class, e.g., 'fab fa-laravel'">
                    @error('icon')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="mt-6">
                <button type="submit" class="inline-flex items-center px-4 py-2 bg-primary-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-primary-700 active:bg-primary-900 focus:outline-none focus:border-primary-900 focus:ring ring-primary-300 disabled:opacity-25 transition ease-in-out duration-150">
                    Add Tech Stack
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800">Existing Tech Stacks</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Slug</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Icon</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($techStacks as $techStack)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $techStack->id }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <span class="mr-2">{{ $techStack->name }}</span>
                            @if($techStack->icon)
                                <i class="{{ $techStack->icon }}"></i>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $techStack->slug }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            @if($techStack->icon)
                                <i class="{{ $techStack->icon }}"></i> {{ $techStack->icon }}
                            @else
                                -
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                <button type="button" @click="editingTechStack = {{ $techStack->id }}; editForm.name = '{{ addslashes($techStack->name) }}'; editForm.icon = '{{ addslashes($techStack->icon ?? '') }}'"
                                        class="text-indigo-600 hover:text-indigo-900">Edit</button>
                                <form method="POST" action="{{ route('admin.tech-stacks.destroy', $techStack) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 ml-2"
                                            onclick="return confirm('Are you sure you want to delete this tech stack?')">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">No tech stacks found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($techStacks->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $techStacks->links() }}
        </div>
        @endif
    </div>

    <!-- Edit Modal -->
    <div x-show="editingTechStack !== null" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full" x-cloak>
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <form :action="'{{ route('admin.tech-stacks.update', '') }}/' + editingTechStack" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="mb-4">
                        <label for="edit_name" class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" name="name" id="edit_name" x-model="editForm.name" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500">
                    </div>
                    <div class="mb-4">
                        <label for="edit_icon" class="block text-sm font-medium text-gray-700 mb-1">Icon (Optional)</label>
                        <input type="text" name="icon" id="edit_icon" x-model="editForm.icon"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500"
                               placeholder="Font Awesome class, e.g., 'fab fa-laravel'">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="editingTechStack = null" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('techStackManager', () => ({
            editingTechStack: null,
            editForm: {
                name: '',
                icon: ''
            }
        }));
    });
</script>
@endpush

@endsection
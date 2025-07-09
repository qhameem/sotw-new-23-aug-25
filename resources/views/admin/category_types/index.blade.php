@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 mt-10">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold">Category Types</h1>
        <a href="{{ route('admin.category-types.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Add Type</a>
    </div>
    @if(session('success'))
        <div class="mb-4 text-green-700 bg-green-100 rounded p-2">{{ session('success') }}</div>
    @endif
    <table class="min-w-full bg-white border rounded">
        <thead>
            <tr>
                <th class="px-4 py-2 border-b">Type</th>
                <th class="px-4 py-2 border-b">Categories</th>
                <th class="px-4 py-2 border-b">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($types as $type)
                <tr>
                    <td class="px-4 py-2 border-b">{{ $type->name }}</td>
                    <td class="px-4 py-2 border-b">
                        @foreach($type->categories as $cat)
                            <span class="inline-block bg-gray-100 text-gray-700 px-2 py-0.5 rounded text-xs mr-1 mb-1">{{ $cat->name }}</span>
                        @endforeach
                    </td>
                    <td class="px-4 py-2 border-b">
                        <a href="{{ route('admin.category-types.edit', $type) }}" class="text-blue-600 hover:underline mr-2">Edit</a>
                        <form action="{{ route('admin.category-types.destroy', $type) }}" method="POST" class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:underline" onclick="return confirm('Delete this type?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection 
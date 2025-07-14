@extends('layouts.app')

@section('header-title')
    <div class="flex items-center">
        <a href="{{ route('admin.users.index') }}" class="mr-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-gray-500 hover:text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
        </a>
        <h1 class="text-xl font-semibold text-gray-800">{{ $user->name }}</h1>
    </div>
@endsection

@section('content')
    <div class="p-4">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-lg font-semibold mb-4">User Details</h2>
            <p><strong>Email:</strong> {{ $user->email }}</p>
            <p><strong>Joined:</strong> {{ $user->created_at->format('M d, Y') }}</p>
        </div>

        <div class="bg-white rounded-lg shadow-md">
            <h2 class="text-lg font-semibold p-6">Submitted Products</h2>
            @forelse($user->products as $product)
                <x-admin.product-list-item :product="$product" />
            @empty
                <div class="text-center py-12">
                    <p class="text-gray-500">This user has not submitted any products.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
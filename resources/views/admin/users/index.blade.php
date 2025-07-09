@extends('layouts.app')

@section('content')
@section('title')
    <h1 class="text-xl font-semibold text-gray-800">Manage Users</h1>
@endsection

@section('actions')
@endsection

@section('content')
    <div class="p-4">
        @if(session('success'))
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4 shadow">{{ session('success') }}</div>
        @endif
        @if(session('error'))
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4 shadow">{{ session('error') }}</div>
        @endif

        <!-- Users List -->
        <div class="bg-white">
            @forelse($users as $user)
                <x-admin.user-list-item :user="$user" />
            @empty
                <div class="text-center py-12">
                    <p class="text-gray-500">No users found.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
@endsection
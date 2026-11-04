@extends('layouts.admin')

@section('title', 'Feedback')
@section('header-title', 'Feedback ('.$feedback->total().')')

@section('content')
<div class="w-full px-4 py-12 sm:px-6 lg:px-8">
    <div class="overflow-hidden rounded-lg bg-white shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        @foreach (['Feedback', 'Submitted by', 'Contact email', 'Submitted at'] as $heading)
                            <th scope="col" class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500">{{ $heading }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($feedback as $item)
                        <tr>
                            <td class="px-4 py-4 align-top text-sm text-gray-900">
                                <div class="font-semibold">#{{ $item->id }} · {{ ucfirst($item->type) }}</div>
                                <p class="mt-2 max-w-xl whitespace-pre-wrap break-words [overflow-wrap:anywhere]">{{ $item->message }}</p>
                            </td>
                            <td class="px-4 py-4 align-top text-sm text-gray-700">
                                @if ($item->user)
                                    <a href="{{ route('admin.users.show', $item->user) }}" class="font-medium text-primary-600 hover:underline">{{ $item->user_name }}</a>
                                    <div>User #{{ $item->user_id }}</div>
                                @else
                                    <span>{{ $item->user_name ? $item->user_name.' (deleted account)' : 'Guest' }}</span>
                                @endif
                                <div class="mt-1">{{ $item->user_email }}</div>
                            </td>
                            <td class="px-4 py-4 align-top text-sm text-gray-700">{{ $item->contact_email ?: 'Not provided' }}</td>
                            <td class="whitespace-nowrap px-4 py-4 align-top text-sm text-gray-700">{{ $item->created_at->format('Y-m-d H:i:s') }} {{ config('app.timezone') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No feedback yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-4">{{ $feedback->links() }}</div>
    </div>
</div>
@endsection

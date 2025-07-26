@extends('layouts.app')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
    <h1 class="text-2xl font-bold py-10 pt-12">Email Logs</h1>

    <div class="overflow-x-auto">
        <table class="min-w-full bg-white border rounded">
            <thead>
                <tr>
                    <th class="px-4 py-2 border-b text-left">Product</th>
                    <th class="px-4 py-2 border-b text-left">User</th>
                    <th class="px-4 py-2 border-b text-left">Status</th>
                    <th class="px-4 py-2 border-b text-left">Message</th>
                    <th class="px-4 py-2 border-b text-left">Timestamp</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                    <tr>
                        <td class="px-4 py-2 border-b align-top">{{ $log->product_id }}</td>
                        <td class="px-4 py-2 border-b align-top">{{ $log->user_id }}</td>
                        <td class="px-4 py-2 border-b align-top">
                            <span class="inline-block px-2 py-1 text-xs font-semibold rounded-full {{ $log->status === 'failed' ? 'bg-red-200 text-red-800' : ($log->status === 'queued' ? 'bg-green-200 text-green-800' : 'bg-gray-200 text-gray-800') }}">
                                {{ $log->status }}
                            </span>
                        </td>
                        <td class="px-4 py-2 border-b align-top">{{ $log->message }}</td>
                        <td class="px-4 py-2 border-b align-top">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-gray-400 text-center py-4">No email logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">
        {{ $logs->links() }}
    </div>
</div>
@endsection
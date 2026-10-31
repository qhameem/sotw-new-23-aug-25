@extends('layouts.admin')

@section('title', 'Users')
@section('header-title', 'Users')

@section('content')
    @if(session('success'))
        <div class="admin-notice" role="status">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="admin-notice is-error" role="alert">{{ session('error') }}</div>
    @endif
    <div class="admin-section-heading"><h2>Registered users</h2><p>{{ number_format($userCount) }} users. View profiles and submitted products.</p></div>
    <div class="admin-table-wrap">
        <table class="admin-users-table">
            <thead><tr>@foreach(['User', 'Email', 'Role', 'Products', 'Joined', ''] as $heading)<th scope="col">{{ $heading }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td><a href="{{ route('admin.users.show', $user) }}" class="admin-user-identity"><span class="approval-submitter-avatar" aria-hidden="true">{{ mb_substr($user->name ?: 'U', 0, 1) }}</span><span>{{ $user->name ?: 'Unnamed user' }}</span></a></td>
                        <td>{{ $user->email }}</td>
                        <td><span class="admin-status {{ $user->hasRole('admin') ? 'is-admin' : '' }}">{{ $user->hasRole('admin') ? 'Admin' : 'Member' }}</span></td>
                        <td>{{ number_format($user->products_count) }}</td>
                        <td>{{ $user->created_at->format('M j, Y') }}</td>
                        <td><a href="{{ route('admin.users.show', $user) }}" class="admin-row-action" aria-label="View {{ $user->name ?: 'user' }}">View <x-admin.approval-icon name="arrow" width="14" height="14" /></a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="admin-empty">No users found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <footer class="approval-table-footer">
        <span>{{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }} of {{ number_format($userCount) }} users</span>
        {{ $users->onEachSide(1)->links('admin.pagination') }}
    </footer>
@endsection

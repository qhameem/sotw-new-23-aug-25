@extends(auth()->user()?->hasRole('admin') ? 'layouts.admin' : 'layouts.app')

@section('header-title', 'Dashboard')

@section('content')
    @if(auth()->user()?->hasRole('admin'))
        @include('admin.dashboard')
    @else
    <div class="p-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900">
                {{ __("You're logged in!") }}
            </div>
        </div>
    </div>
    @endif
@endsection

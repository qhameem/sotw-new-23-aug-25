@extends('layouts.app')

@section('title')
    <h1 class="text-xl font-bold text-gray-800">{{ __('Profile') }}</h1>
@endsection

@section('actions')
@endsection

@section('content')
    <div class="p-4 space-y-6">
        <div class="p-4 sm:p-8 bg-white border border-gray-100 sm:rounded-lg">
            <div class="max-w-md">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white border border-gray-100 sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white border border-gray-100 sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.update-notification-preferences-form')
            </div>
        </div>

        <div class="p-4 sm:p-8 bg-white border border-gray-100 sm:rounded-lg">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
@endsection

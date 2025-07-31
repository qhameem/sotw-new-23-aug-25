@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-semibold text-gray-900 mb-6">Edit Changelog Entry</h1>

    <div class="bg-white shadow-md rounded-lg p-6">
        <form action="{{ route('admin.changelogs.update', $changelog) }}" method="POST">
            @method('PUT')
            @include('admin.changelogs._form', ['changelog' => $changelog])
        </form>
    </div>
</div>
@endsection
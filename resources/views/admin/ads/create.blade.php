@extends('layouts.app')

@section('title', 'Create New Ad')

@section('content')
    <div class="container mx-auto px-4 py-8">
        @include('admin.ads._form')
    </div>
@endsection

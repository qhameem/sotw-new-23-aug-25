@extends('layouts.app')

@section('title', 'Edit Ad Zone')

@section('content')
    <div class="container mx-auto px-4 py-8">
        @include('admin.ad_zones._form')
    </div>
@endsection

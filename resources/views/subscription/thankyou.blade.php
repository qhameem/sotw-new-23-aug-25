@extends('layouts.app')

@section('content')
    <div class="container py-5 text-center">
        <h1 class="text-2xl font-bold mb-4">Thank you for subscribing!</h1>
        <p class="text-gray-700">Your payment was successful. You now have access to premium features.</p>
        <a href="{{ route('dashboard') }}" class="btn btn-primary mt-4">Go to Dashboard</a>
    </div>
@endsection

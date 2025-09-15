@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold mb-4">How to add a Software on the Web badge to your website</h1>
    <p class="mb-8">Show your support and link back to us by placing one of our badges on your site. Simply copy the HTML embed code below the badge of your choice and paste it into your website's HTML where you would like it to appear.</p>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        @foreach ($badges as $badge)
            <div class="bg-white shadow-md rounded-lg p-6">
                <h2 class="text-xl font-bold mb-2">{{ $badge->title }}</h2>
                <div class="mb-4">
                    <img src="{{ url($badge->path) }}" alt="{{ $badge->alt_text }}" style="max-width:200px; height:auto; border:0;">
                </div>
                <div class="mb-4">
                    <label for="embed_code_{{ $badge->id }}" class="block text-gray-700 text-sm font-bold mb-2">Embed Code:</label>
                    <textarea id="embed_code_{{ $badge->id }}" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" rows="5" readonly><a href="{{ url('/') }}" target="_blank" rel="dofollow">
    <img src="{{ url($badge->path) }}" 
         alt="{{ $badge->alt_text }}" 
         style="max-width:200px; height:auto; border:0;" />
</a></textarea>
                </div>
                <button onclick="copyToClipboard('embed_code_{{ $badge->id }}')" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Copy Code
                </button>
            </div>
        @endforeach
    </div>
</div>

<script>
    function copyToClipboard(elementId) {
        var copyText = document.getElementById(elementId);
        copyText.select();
        copyText.setSelectionRange(0, 99999); /* For mobile devices */
        document.execCommand("copy");
        alert("Copied the text: " + copyText.value);
    }
</script>
@endsection
@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">Edit Email Template</h1>

    @if(session('success'))
        <div class="mb-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white shadow sm:rounded-lg p-6">
        <form action="{{ route('admin.settings.storeEmailTemplates') }}" method="POST">
            @csrf
            <div class="mb-4">
                <label for="subject" class="block text-sm font-medium text-gray-700">Subject</label>
                <input type="text" name="subject" id="subject" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" value="{{ old('subject', $template->subject) }}">
                @error('subject')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="from_name" class="block text-sm font-medium text-gray-700">From Name</label>
                <input type="text" name="from_name" id="from_name" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" value="{{ old('from_name', $template->from_name) }}">
                @error('from_name')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="from_email" class="block text-sm font-medium text-gray-700">From Email</label>
                <input type="email" name="from_email" id="from_email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" value="{{ old('from_email', $template->from_email) }}">
                @error('from_email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="reply_to_email" class="block text-sm font-medium text-gray-700">Reply To Email</label>
                <input type="email" name="reply_to_email" id="reply_to_email" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-primary-500 focus:border-primary-500" value="{{ old('reply_to_email', $template->reply_to_email) }}">
                @error('reply_to_email')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4">
                <label for="body" class="block text-sm font-medium text-gray-700">Body</label>
                <div id="editor" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm bg-white" style="min-height: 200px;"></div>
                <textarea name="body" id="body_hidden" class="hidden">{{ old('body', $template->body) }}</textarea>
                @error('body')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="mb-4 flex items-center">
                <input type="checkbox" name="is_html" id="is_html" value="1" class="h-4 w-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500" {{ old('is_html', $template->is_html) ? 'checked' : '' }}>
                <label for="is_html" class="ml-2 block text-sm text-gray-900">Send as HTML</label>
            </div>
            <div class="mb-6">
                <p class="text-sm text-gray-600">Allowed Variables: <code class="font-mono text-xs text-gray-800">{{ implode(', ', $template->allowed_variables) }}</code></p>
                <p class="text-sm text-gray-600">Example: <code>@{{ product_publish_datetime }}</code> will be replaced with the product's scheduled publish date and time.</p>
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                Save Email Template
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
<script>
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                ['bold', 'italic', 'underline', 'strike'],        // toggled buttons
                ['blockquote', 'code-block'],

                [{ 'header': 1 }, { 'header': 2 }],               // custom button values
                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                [{ 'script': 'sub'}, { 'script': 'super' }],      // superscript/subscript
                [{ 'indent': '-1'}, { 'indent': '+1' }],          // outdent/indent
                [{ 'direction': 'rtl' }],                         // text direction

                [{ 'size': ['small', false, 'large', 'huge'] }],  // custom dropdown
                [{ 'header': [1, 2, 3, 4, 5, 6, false] }],

                [{ 'color': [] }, { 'background': [] }],          // dropdown with defaults from theme
                [{ 'font': [] }],
                [{ 'align': [] }],

                ['link', 'image'],

                ['clean']                                         // remove formatting button
            ]
        }
    });

    // Set initial content
    quill.root.innerHTML = document.querySelector('#body_hidden').value;

    // On form submit, populate the hidden textarea with Quill's HTML content
    document.querySelector('form').onsubmit = function() {
        document.querySelector('#body_hidden').value = quill.root.innerHTML;
    };
</script>
@endpush
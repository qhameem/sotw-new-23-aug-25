@php($headerCodeInjection = app(\App\Support\HeaderCodeInjection::class)->forRequest(request()))
@if($headerCodeInjection !== '')
    {!! $headerCodeInjection !!}
@endif

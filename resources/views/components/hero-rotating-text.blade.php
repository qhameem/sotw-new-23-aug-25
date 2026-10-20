@props(['phrases'])
<span class="inline-grid align-bottom" style="color: var(--color-primary-500) !important;">
    <span class="sr-only">{{ $phrases[0] }}</span>
    <span class="col-start-1 row-start-1 break-words" style="color: inherit !important;" aria-hidden="true" x-text="text">{{ $phrases[0] }}</span>
    @foreach($phrases as $phrase)
        <span class="invisible col-start-1 row-start-1 break-words" aria-hidden="true">{{ $phrase }}</span>
    @endforeach
</span>

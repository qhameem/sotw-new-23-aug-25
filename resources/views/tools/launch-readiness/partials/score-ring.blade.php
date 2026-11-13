@php
    $ringColors = match (true) {
                $score >= 90 => 'border-emerald-500 bg-emerald-50 text-emerald-700',
                $score >= 75 => 'border-amber-400 bg-amber-50 text-amber-700',
                default => 'border-rose-400 bg-rose-50 text-rose-600',
            };
@endphp

<span @class([
    'inline-flex shrink-0 items-center justify-center rounded-full border font-semibold',
    'h-5 w-5 text-[10px]' => $compact ?? false,
    'h-10 w-10 text-sm' => !($compact ?? false),
    $ringColors,
]) aria-label="Launch score {{ $score }} out of 100">{{ $score }}</span>

<div x-data="{
    timer: null,
    schedule() {
        if (this.timer !== null) return
        this.timer = setTimeout(() => this.$refs.dialog.showModal(), @js($modal['delay'] * 1000))
    },
    init() {
        if (document.readyState === 'complete') this.schedule()
    },
    destroy() {
        clearTimeout(this.timer)
    }
}" @load.window="schedule()">
    <dialog x-ref="dialog" aria-labelledby="submission-modal-title"
        class="fixed inset-0 m-auto max-h-[calc(100dvh-2rem)] w-[calc(100%-2rem)] {{ $modal['ad_type'] === 'offer' && $modal['offer_width'] === 'wide' ? 'max-w-2xl' : 'max-w-lg' }} overflow-y-auto rounded-2xl border border-gray-200 bg-white p-5 text-gray-900 shadow-xl backdrop:bg-gray-900/60 sm:p-6">
        @if($modal['ad_type'] === 'offer')
            @include('products.partials._submission-success-offer')
        @else
            @include('products.partials._submission-success-modal-content')
        @endif
    </dialog>
</div>

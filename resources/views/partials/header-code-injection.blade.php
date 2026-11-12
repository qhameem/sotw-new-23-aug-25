@php($headerCodeInjection = app(\App\Support\HeaderCodeInjection::class)->forRequest(request()))
@if($headerCodeInjection !== '')
    {!! $headerCodeInjection !!}
    @if($reserveStartupBarSpace ?? false)
        <script>
            (() => {
                const loader = [...document.scripts].find(script =>
                    /^https:\/\/(?:www\.)?startupbar\.co\/widget\/loader\.js(?:[?#]|$)/i.test(script.src)
                    && script.dataset.startupId && script.dataset.mode !== 'track'
                );

                if (!loader || window !== window.top) return;

                const root = document.documentElement;
                const origin = new URL(loader.src).origin;
                let placement = 'top';

                try {
                    if (sessionStorage.getItem('startupbar:dismissed') === '1') return;
                    placement = localStorage.getItem('startupbar:placement:' + loader.dataset.startupId) || placement;
                } catch {}

                root.toggleAttribute('data-reserve-startupbar', placement !== 'bottom');

                window.addEventListener('message', event => {
                    const frame = document.querySelector('iframe[data-startupbar-frame="1"]');
                    if (!frame || event.origin !== origin || event.source !== frame.contentWindow) return;

                    if (event.data?.type === 'startupbar:placement') {
                        root.toggleAttribute('data-reserve-startupbar', event.data.placement !== 'bottom');
                    } else if (event.data?.type === 'startupbar:resize' && Math.round(event.data.height) === 0) {
                        root.removeAttribute('data-reserve-startupbar');
                    }
                });
            })();
        </script>
    @endif
@endif

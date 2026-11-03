<!doctype html>
<html><head><meta charset="utf-8"><base target="_blank">
<style>html,body{margin:0;padding:0;overflow:hidden}#badge{display:flow-root;width:max-content}img,svg{vertical-align:middle}</style>
</head><body><div id="badge">{!! $code !!}</div>
<script>
(() => {
    const badge = document.getElementById('badge');
    let previous = '';
    const measure = () => {
        const bounds = badge.getBoundingClientRect();
        const width = Math.ceil(Math.max(bounds.width, badge.scrollWidth));
        const height = Math.ceil(Math.max(bounds.height, badge.scrollHeight));
        const size = `${width}:${height}`;
        if (width > 0 && height > 0 && size !== previous) {
            previous = size;
            parent.postMessage({ type: 'launch-readiness-badge-size', width, height }, '*');
        }
    };
    new ResizeObserver(measure).observe(badge);
    new MutationObserver(measure).observe(badge, { childList: true, subtree: true, attributes: true });
    document.addEventListener('load', measure, true);
    window.addEventListener('message', event => {
        if (event.source === parent && event.data?.type === 'launch-readiness-badge-measure') {
            previous = '';
            measure();
        }
    });
    measure();
})();
</script></body></html>

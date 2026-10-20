<div role="status"
    x-data="{ visible: true, timer: null, init() { this.timer = setTimeout(() => { this.visible = false }, 3000) }, destroy() { clearTimeout(this.timer) } }"
    x-show="visible"
    {{ $attributes }}>
    {{ $slot }}
</div>

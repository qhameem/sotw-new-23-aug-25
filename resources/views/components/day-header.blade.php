@props(['dayOfYear', 'fullDate', 'nextLaunchTime'])

<div class="px-4 py-2 bg-gradient-to-t from-white to-stone-50 flex items-center justify-between">
    <div>
        <h2 class="text-lg font-medium font-noto-serif text-gray-800">Day {{ $dayOfYear }}</h2>
        <p class="text-xs text-gray-500">{{ $fullDate }}</p>
    </div>
    @if($nextLaunchTime)
        <div id="countdown" class="text-xs text-gray-700"></div>
    @endif
</div>

@if($nextLaunchTime)
<script>
    (function() {
        const countdownElement = document.getElementById('countdown');
        if (!countdownElement) return;

        const nextLaunchTime = new Date("{{ $nextLaunchTime }}").getTime();

        function updateCountdown() {
            const now = new Date().getTime();
            const distance = nextLaunchTime - now;

            if (distance < 0) {
                countdownElement.innerHTML = "Launched!";
                if(typeof interval !== 'undefined') {
                    clearInterval(interval);
                }
                return;
            }

            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

            if (hours === 0 && minutes === 0) {
                countdownElement.innerHTML = "Next launch in less than a minute";
                return;
            }

            let parts = [];
            if (hours > 0) {
                parts.push(`${hours}h${hours !== 1 ? '' : ''}`);
            }
            if (minutes > 0) {
                parts.push(`${minutes}m${minutes !== 1 ? '' : ''}`);
            }

            countdownElement.innerHTML = `Next launch in ${parts.join('  ')}`;
        }

        const interval = setInterval(updateCountdown, 60000);
        updateCountdown();
    })();
</script>
@endif
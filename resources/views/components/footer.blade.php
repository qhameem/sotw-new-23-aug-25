<footer class="w-full p-4 border-t md:flex md:items-center md:justify-center md:p-6">
    <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
        <a href="{{ route('about') }}" class="hover:underline">About</a> •
        <a href="{{ route('faq') }}" class="hover:underline">FAQ</a> •
        <a href="{{ route('legal') }}" class="hover:underline">Privacy Policy</a> •
        <a href="{{ route('changelog.index') }}" class="hover:underline">Changelog</a> •
        <a href="{{ route('badges.index') }}" class="hover:underline">Badge</a> •
        <span x-data="{ open: false }" class="relative inline-block text-left align-middle">
            <button
                type="button"
                @click="open = !open"
                class="inline-flex items-center gap-1 hover:underline"
                :aria-expanded="open.toString()"
                aria-haspopup="true"
            >
                <span>Free Tools</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div
                x-show="open"
                x-cloak
                @click.away="open = false"
                class="absolute left-1/2 z-50 mt-2 w-40 -translate-x-1/2 rounded-md bg-white py-1 text-left shadow-lg ring-1 ring-black ring-opacity-5"
                style="display: none;"
            >
                <a href="{{ route('todolists.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">To-Do List</a>
            </div>
        </span> •
        <a href="https://x.com/software_on_web" target="_blank" class="hover:underline">X.com</a> •
        Contact us: <a href="mailto:hello@softwareontheweb.com" target="_blank" class="hover:underline">hello@softwareontheweb.com</a>
        <div class="h-2"></div>
        <span class="text-gray-400 " x-data="{ time: new Date() }" x-init="setInterval(() => time = new Date(), 1000)">
            <span x-text="time.toLocaleString('en-GB', { timeZone: 'UTC', day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false })"></span> UTC
        </span> © {{ date('Y') }} Software on the web
    </div>
</footer>

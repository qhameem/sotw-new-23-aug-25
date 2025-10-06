<footer class="w-full p-4 bg-white border-t border-gray-200 shadow md:flex md:items-center md:justify-center md:p-6 dark:bg-gray-800 dark:border-gray-600">
    <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
        <a href="{{ route('promote') }}" class="hover:underline">Pricing</a> •
        <a href="{{ route('about') }}" class="hover:underline">About</a> •
        <a href="{{ route('faq') }}" class="hover:underline">FAQ</a> •
        <a href="{{ route('legal') }}" class="hover:underline">Privacy Policy</a> •
        <a href="{{ route('changelog.index') }}" class="hover:underline">Changelog</a> •
        <a href="{{ route('badges.index') }}" class="hover:underline">Badge</a> •
        <a href="https://x.com/software_on_web" target="_blank" class="hover:underline">X.com</a>
        <div class="h-2"></div>
        <span class="text-gray-400 " x-data="{ time: new Date() }" x-init="setInterval(() => time = new Date(), 1000)">
            <span x-text="time.toLocaleString('en-GB', { timeZone: 'UTC', day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false })"></span> UTC
        </span> © 2025 Software on the web
    </div>
</footer>
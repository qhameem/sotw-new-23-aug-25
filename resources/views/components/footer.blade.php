<footer class="w-full p-4 border-t md:flex md:items-center md:justify-center md:p-6">
    <div class="text-xs text-gray-500 dark:text-gray-400 text-center">
        <a href="{{ route('promote') }}" class="hover:underline">Pricing</a> •
        <a href="{{ route('about') }}" class="hover:underline">About</a> •
        <a href="{{ route('faq') }}" class="hover:underline">FAQ</a> •
        <a href="{{ route('legal') }}" class="hover:underline">Privacy Policy</a> •
        <a href="{{ route('changelog.index') }}" class="hover:underline">Changelog</a> •
        <a href="{{ route('badges.index') }}" class="hover:underline">Badge</a> •
        <a href="https://x.com/software_on_web" target="_blank" class="hover:underline">X.com</a> •
        Contact us: <a href="mailto:hello@softwareontheweb.com" target="_blank" class="hover:underline">hello@softwareontheweb.com</a>
        <div class="h-2"></div>
        <span class="text-gray-400 " x-data="{ time: new Date() }" x-init="setInterval(() => time = new Date(), 1000)">
            <span x-text="time.toLocaleString('en-GB', { timeZone: 'UTC', day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit', hour12: false })"></span> UTC
        </span> © {{ date('Y') }} Software on the web
    </div>
</footer>
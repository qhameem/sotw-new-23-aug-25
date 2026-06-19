<div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false">
    <button
        type="button"
        @click="open = ! open"
        class="inline-flex items-center gap-1.5 rounded-full px-1 py-1 pr-2 transition"
        :class="'bg-[var(--lr-panel-soft)] text-[var(--lr-text)]'"
        aria-label="User menu"
    >
        @if($toolUser?->avatarUrl())
            <img src="{{ $toolUser->avatarUrl() }}" alt="{{ $toolUser->email }}" class="h-9 w-9 rounded-full object-cover">
        @else
            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-[var(--lr-panel-strong)] text-sm font-semibold text-[var(--lr-text)]">
                {{ $toolUser?->initials() ?: 'TU' }}
            </span>
        @endif
        <svg class="h-3.5 w-3.5 text-[var(--lr-muted)]" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.937a.75.75 0 1 1 1.08 1.04l-4.25 4.51a.75.75 0 0 1-1.08 0l-4.25-4.51a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-cloak
        x-show="open"
        x-transition.origin.top.right
        @click.outside="open = false"
        class="absolute right-0 z-40 mt-3 w-72 overflow-hidden rounded-xl border shadow-2xl"
        :class="'border-[var(--lr-border)] bg-[var(--lr-panel)] text-[var(--lr-text)]'"
    >
        <div class="border-b px-4 py-4" :class="'border-[var(--lr-border)]'">
            <div class="flex items-center gap-3">
                @if($toolUser?->avatarUrl())
                    <img src="{{ $toolUser->avatarUrl() }}" alt="{{ $toolUser->email }}" class="h-10 w-10 rounded-xl object-cover">
                @else
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-[var(--lr-panel-strong)] text-sm font-semibold text-[var(--lr-text)]">
                        {{ $toolUser?->initials() ?: 'TU' }}
                    </span>
                @endif
                <div class="min-w-0">
                    <p class="truncate text-sm font-semibold text-[var(--lr-text)]">{{ $toolUser->email }}</p>
                    @if($toolUserIsAdmin ?? false)
                        <p class="mt-1 inline-flex rounded-full border border-emerald-500/30 bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-400">Admin</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-2">
            <a href="{{ route('launch-readiness.dashboard', ['toolSlug' => $toolSlug]) }}" @click="open = false" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-[var(--lr-muted)] transition hover:bg-[var(--lr-panel-strong)] hover:text-[var(--lr-text)]">
                <svg class="h-4 w-4" viewBox="0 0 1920 1920" fill="currentColor" aria-hidden="true">
                    <path d="M833.935 1063.327c28.913 170.315 64.038 348.198 83.464 384.79 27.557 51.84 92.047 71.944 144 44.387 51.84-27.558 71.717-92.273 44.16-144.113-19.426-36.593-146.937-165.46-271.624-285.064Zm-43.821-196.405c61.553 56.923 370.899 344.81 415.285 428.612 56.696 106.842 15.811 239.887-91.144 296.697-32.64 17.28-67.765 25.411-102.325 25.411-78.72 0-154.955-42.353-194.371-116.555-44.386-83.802-109.102-501.346-121.638-584.245-3.501-23.717 8.245-47.21 29.365-58.277 21.346-11.294 47.096-8.02 64.828 8.357ZM960.045 281.99c529.355 0 960 430.757 960 960 0 77.139-8.922 153.148-26.654 225.882l-10.39 43.144h-524.386v-112.942h434.258c9.487-50.71 14.231-103.115 14.231-156.084 0-467.125-380.047-847.06-847.059-847.06-467.125 0-847.059 379.935-847.059 847.06 0 52.97 4.744 105.374 14.118 156.084h487.454v112.942H36.977l-10.39-43.144C8.966 1395.137.044 1319.128.044 1241.99c0-529.243 430.645-960 960-960Zm542.547 390.686 79.85 79.85-112.716 112.715-79.85-79.85 112.716-112.715Zm-1085.184 0L530.123 785.39l-79.85 79.85L337.56 752.524l79.849-79.85Zm599.063-201.363v159.473H903.529V471.312h112.942Z" fill-rule="evenodd" />
                </svg>
                <span>Dashboard</span>
            </a>

            <a href="{{ route('launch-readiness.settings', ['toolSlug' => $toolSlug]) }}" @click="open = false" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-[var(--lr-muted)] transition hover:bg-[var(--lr-panel-strong)] hover:text-[var(--lr-text)]">
                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="1.5"></circle>
                    <path d="M13.7654 2.15224C13.3978 2 12.9319 2 12 2C11.0681 2 10.6022 2 10.2346 2.15224C9.74457 2.35523 9.35522 2.74458 9.15223 3.23463C9.05957 3.45834 9.0233 3.7185 9.00911 4.09799C8.98826 4.65568 8.70226 5.17189 8.21894 5.45093C7.73564 5.72996 7.14559 5.71954 6.65219 5.45876C6.31645 5.2813 6.07301 5.18262 5.83294 5.15102C5.30704 5.08178 4.77518 5.22429 4.35436 5.5472C4.03874 5.78938 3.80577 6.1929 3.33983 6.99993C2.87389 7.80697 2.64092 8.21048 2.58899 8.60491C2.51976 9.1308 2.66227 9.66266 2.98518 10.0835C3.13256 10.2756 3.3397 10.437 3.66119 10.639C4.1338 10.936 4.43789 11.4419 4.43786 12C4.43783 12.5581 4.13375 13.0639 3.66118 13.3608C3.33965 13.5629 3.13248 13.7244 2.98508 13.9165C2.66217 14.3373 2.51966 14.8691 2.5889 15.395C2.64082 15.7894 2.87379 16.193 3.33973 17C3.80568 17.807 4.03865 18.2106 4.35426 18.4527C4.77508 18.7756 5.30694 18.9181 5.83284 18.8489C6.07289 18.8173 6.31632 18.7186 6.65204 18.5412C7.14547 18.2804 7.73556 18.27 8.2189 18.549C8.70224 18.8281 8.98826 19.3443 9.00911 19.9021C9.02331 20.2815 9.05957 20.5417 9.15223 20.7654C9.35522 21.2554 9.74457 21.6448 10.2346 21.8478C10.6022 22 11.0681 22 12 22C12.9319 22 13.3978 22 13.7654 21.8478C14.2554 21.6448 14.6448 21.2554 14.8477 20.7654C14.9404 20.5417 14.9767 20.2815 14.9909 19.902C15.0117 19.3443 15.2977 18.8281 15.781 18.549C16.2643 18.2699 16.8544 18.2804 17.3479 18.5412C17.6836 18.7186 17.927 18.8172 18.167 18.8488C18.6929 18.9181 19.2248 18.7756 19.6456 18.4527C19.9612 18.2105 20.1942 17.807 20.6601 16.9999C21.1261 16.1929 21.3591 15.7894 21.411 15.395C21.4802 14.8691 21.3377 14.3372 21.0148 13.9164C20.8674 13.7243 20.6602 13.5628 20.3387 13.3608C19.8662 13.0639 19.5621 12.558 19.5621 11.9999C19.5621 11.4418 19.8662 10.9361 20.3387 10.6392C20.6603 10.4371 20.8675 10.2757 21.0149 10.0835C21.3378 9.66273 21.4803 9.13087 21.4111 8.60497C21.3592 8.21055 21.1262 7.80703 20.6602 7C20.1943 6.19297 19.9613 5.78945 19.6457 5.54727C19.2249 5.22436 18.693 5.08185 18.1671 5.15109C17.9271 5.18269 17.6837 5.28136 17.3479 5.4588C16.8545 5.71959 16.2644 5.73002 15.7811 5.45096C15.2977 5.17191 15.0117 4.65566 14.9909 4.09794C14.9767 3.71848 14.9404 3.45833 14.8477 3.23463C14.6448 2.74458 14.2554 2.35523 13.7654 2.15224Z" stroke="currentColor" stroke-width="1.5"></path>
                </svg>
                <span>Settings</span>
            </a>

            <form method="POST" action="{{ route('launch-readiness.auth.logout', ['toolSlug' => $toolSlug]) }}" class="mt-1">
                @csrf
                <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm text-[var(--lr-muted)] transition hover:bg-[var(--lr-panel-strong)] hover:text-[var(--lr-text)]">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M3 4.75A1.75 1.75 0 0 1 4.75 3h5.5a.75.75 0 0 1 0 1.5h-5.5a.25.25 0 0 0-.25.25v10.5c0 .138.112.25.25.25h5.5a.75.75 0 0 1 0 1.5h-5.5A1.75 1.75 0 0 1 3 15.25V4.75Zm9.22 2.47a.75.75 0 0 1 1.06 0l2.25 2.25a.75.75 0 0 1 0 1.06l-2.25 2.25a.75.75 0 1 1-1.06-1.06l.97-.97H8.75a.75.75 0 0 1 0-1.5h4.44l-.97-.97a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                    <span>Log out</span>
                </button>
            </form>
        </div>
    </div>
</div>

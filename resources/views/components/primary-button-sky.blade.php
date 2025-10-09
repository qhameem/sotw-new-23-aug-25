<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-1 bg-white border border-sky-500 rounded-md font-semibold text-sm tracking-normal capitalize hover:bg-sky-50 focus:bg-sky-600 text-sky-500 active:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}
        style="">
    {{ $slot }}
</button>

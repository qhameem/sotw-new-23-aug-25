<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-1 bg-white border border-primary-500 rounded-md font-semibold text-sm tracking-normal capitalize hover:bg-rose-50 focus:bg-primary-600 text-primary-500 active:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}
        style="">
    {{ $slot }}
</button>

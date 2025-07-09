<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-4 py-2 bg-primary-500 border border-transparent rounded-md font-semibold text-xs uppercase tracking-widest hover:bg-primary-600 focus:bg-primary-600 text-white active:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2 transition ease-in-out duration-150']) }}
        style="">
    {{ $slot }}
</button>

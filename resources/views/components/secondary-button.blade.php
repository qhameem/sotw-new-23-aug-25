<button {{ $attributes->merge(['type' => 'button', 'class' => 'inline-flex items-center px-4 py-1    bg-white  border border-gray-300  rounded-md font-semibold text-sm text-gray-500  capitalize tracking-normal shadow-sm hover:bg-gray-50  focus:outline-none focus:ring-2 focus:ring-primary-500  focus:ring-offset-2  disabled:opacity-25 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>

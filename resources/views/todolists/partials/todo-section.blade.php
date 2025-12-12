<!-- Todo List Section -->
<div id="todo-app-container" class="bg-white rounded-lg p-6 w-full max-w-2xl" data-lists="{{ json_encode($lists ?? []) }}" data-store-url="{{ route('todolists.store') }}" data-base-url="{{ url('/free-todo-list-tool') }}" data-csrf-token="{{ csrf_token() }}">
    <todo-list 
        :initial-lists="{{ json_encode($lists ?? []) }}"
        :store-url="'{{ route('todolists.store') }}'"
        :base-url="'{{ url('/free-todo-list-tool') }}'"
        :csrf-token="'{{ csrf_token() }}'"
    ></todo-list>
</div>

<footer class="text-center mt-8">
    <p class="text-xs text-gray-400">
        A free Todo list tool by
        <a href="{{ route('home') }}" class="underline hover:text-gray-60">
            Software on the Web
        </a>
    </p>
</footer>
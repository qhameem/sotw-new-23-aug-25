@if(isset($lists) && $lists->count() > 0)
    <div class="bg-white p-4 rounded-lg">
        <h3 class="text-lg font-semibold mb-4">Your Lists</h3>
        <ul class="space-y-2">
            @foreach($lists as $list)
                <li>
                    <a href="{{ url('/free-todo-list-tool#' . $list->id) }}" class="text-blue-500 hover:underline">{{ $list->title }}</a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
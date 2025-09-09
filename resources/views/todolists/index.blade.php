@extends('layouts.todolist')

@section('title', 'Your To Do')

@section('content')
<div id="todo-app-container" class="bg-white rounded-lg shadow-md p-6 w-full max-w-2xl" data-lists="{{ json_encode($lists) }}" data-store-url="{{ route('todolists.store') }}" data-base-url="{{ url('/free-todo-list-tool') }}" data-csrf-token="{{ csrf_token() }}">
    
    <h1 class="text-2xl font-bold text-gray-800 mb-6">Your To Do</h1>

    <!-- Add New Task -->
    <div class="flex flex-col sm:flex-row gap-2 mb-6">
        <input type="text" id="new-item-title-input" placeholder="Add new task" class="flex-grow px-1 py-2 bg-transparent border-0 border-b border-gray-300 focus:outline-none focus:ring-0 focus:border-gray-500">
        <button id="add-item-button" class="bg-gray-800 text-white font-bold w-full sm:w-10 h-10 flex items-center justify-center rounded-lg hover:bg-gray-700 transition-colors flex-shrink-0">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" /></svg>
        </button>
    </div>

    <!-- To-Do Lists Container -->
    <div id="todo-lists-container" class="space-y-2">
        <!-- Tasks will be rendered here -->
    </div>

    <!-- Footer -->
    <div class="mt-6 text-center">
        
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const appContainer = document.getElementById('todo-app-container');
    const storeUrl = appContainer.dataset.storeUrl;
    const baseUrl = appContainer.dataset.baseUrl;
    const csrfToken = appContainer.dataset.csrfToken;
    let lists = JSON.parse(appContainer.dataset.lists);
    let activeListId = lists.length > 0 ? lists[0].id : null;

    const newItemTitleInput = document.getElementById('new-item-title-input');
    const addItemButton = document.getElementById('add-item-button');
    const listsContainer = document.getElementById('todo-lists-container');
    const remainingTodosEl = document.getElementById('remaining-todos');
    const quoteEl = document.getElementById('inspirational-quote');

    const quotes = [
        "\"Doing what you love is the cornerstone of having abundance in your life.\" - Wayne Dyer",
        "\"The secret of getting ahead is getting started.\" - Mark Twain",
        "\"It's not the load that breaks you down, it's the way you carry it.\" - Lou Holtz",
        "\"The future depends on what you do today.\" - Mahatma Gandhi"
    ];

    const api = {
        async post(url, data) {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        },
        async delete(url) {
            const response = await fetch(url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        },
        async put(url, data) {
            const response = await fetch(url, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(data)
            });
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        }
    };

    const renderTasks = () => {
        listsContainer.innerHTML = '';
        if (!activeListId) return;

        const list = lists.find(l => l.id == activeListId);
        if (!list || !list.items) return;

        list.items.forEach(item => {
            const taskElement = document.createElement('div');
            const priorityColor = item.color || 'gray';
            taskElement.className = `p-3 border border-gray-200 rounded-lg bg-${priorityColor}-100`;
            
            const deadlineDate = item.deadline ? new Date(item.deadline) : null;
            const formattedDeadline = deadlineDate ? deadlineDate.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : 'Set Deadline';
            const deadlineColorClass = deadlineDate && deadlineDate < new Date() ? 'text-red-500' : 'text-gray-500';

            const priorityColors = {
                'red': 1, 'orange': 2, 'yellow': 3, 'green': 4, 'gray': 5
            };

            const colorOptionsHtml = Object.entries(priorityColors).map(([color, priority]) => `
                <div class="color-option p-1 cursor-pointer flex items-center gap-2" data-color="${color}">
                    <span class="block w-5 h-5 rounded-full bg-${color}-400 hover:ring-2 hover:ring-offset-1 hover:ring-${color}-500"></span>
                    <span class="text-xs text-gray-600">${priority}</span>
                </div>
            `).join('');

            taskElement.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center flex-grow">
                        <input type="checkbox" data-action="toggle-item" data-item-id="${item.id}" class="h-5 w-5 rounded border-gray-300 text-gray-800 focus:ring-gray-700" ${item.completed ? 'checked' : ''}>
                        <div class="ml-3 flex-grow">
                            <span class="item-title text-sm text-gray-800 cursor-pointer ${item.completed ? 'line-through text-gray-500' : ''}">${item.title}</span>
                            <input type="text" class="item-title-input text-sm text-gray-800 border-b border-gray-300 focus:outline-none hidden w-full" value="${item.title}" data-item-id="${item.id}">
                        </div>
                    </div>
                    <button data-action="delete-item" data-item-id="${item.id}" class="text-gray-400 hover:text-gray-600 ml-2 flex-shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="flex items-center justify-between pl-8 mt-1">
                    <div class="relative">
                        <a href="#" data-action="open-color-picker" class="text-xs text-gray-500 hover:underline">Priority ${priorityColors[priorityColor]}</a>
                        <div class="item-color-palette absolute z-10 mt-2 p-2 w-40 bg-white border border-gray-200 rounded-md shadow-lg hidden flex flex-col gap-1">
                            ${colorOptionsHtml}
                        </div>
                    </div>
                    <div class="relative">
                        <a href="#" data-action="open-deadline-picker" data-item-id="${item.id}" class="text-xs ${deadlineColorClass} hover:underline">${formattedDeadline}</a>
                        <input type="datetime-local" data-action="update-deadline" data-item-id="${item.id}" class="item-deadline-input absolute right-0 top-full mt-2 z-10 bg-white border border-gray-300 rounded-md text-sm focus:outline-none hidden" value="${item.deadline ? new Date(item.deadline).toISOString().slice(0, 16) : ''}">
                    </div>
                </div>
            `;
            listsContainer.appendChild(taskElement);
        });

        // updateFooter();
    };

    // const updateFooter = () => {
    //     if (!activeListId) {
    //         remainingTodosEl.textContent = '';
    //         quoteEl.textContent = '';
    //         return;
    //     }
    //     const list = lists.find(l => l.id == activeListId);
    //     const remaining = list ? list.items.filter(i => !i.completed).length : 0;
    //     remainingTodosEl.textContent = `Your remaining todos: ${remaining}`;
    //     if (remaining > 0) {
    //         quoteEl.textContent = quotes[Math.floor(Math.random() * quotes.length)];
    //     } else {
    //         quoteEl.textContent = "\"Well done! You've cleared all your tasks.\"";
    //     }
    // };

    const addItem = async () => {
        const title = newItemTitleInput.value.trim();
        if (title === '' || !activeListId) return;

        try {
            const newItem = await api.post(`${baseUrl}/${activeListId}/items`, { title, color: 'gray' });
            const list = lists.find(l => l.id == activeListId);
            if (list) {
                list.items.push(newItem);
            }
            newItemTitleInput.value = '';
            renderTasks();
        } catch (error) {
            console.error('Failed to add item:', error);
        }
    };

    const updateItemTitle = async (input) => {
        const itemId = input.dataset.itemId;
        const newTitle = input.value.trim();
        const span = input.previousElementSibling;
        const originalTitle = span.textContent;

        if (newTitle === '' || newTitle === originalTitle) {
            input.classList.add('hidden');
            span.classList.remove('hidden');
            return;
        }

        try {
            await api.put(`${baseUrl}/items/${itemId}`, { title: newTitle });
            const list = lists.find(l => l.items.some(i => i.id == itemId));
            if (list) {
                list.items.find(i => i.id == itemId).title = newTitle;
            }
            renderTasks();
        } catch (error) {
            console.error('Failed to update item title:', error);
            renderTasks(); // Re-render to show original state
        }
    };

    listsContainer.addEventListener('click', async (e) => {
        const target = e.target;
        const button = target.closest('button');
        const link = target.closest('a');

        if (link && link.dataset.action === 'open-deadline-picker') {
            e.preventDefault();
            const deadlineInput = link.nextElementSibling;
            deadlineInput.classList.toggle('hidden');
            if (!deadlineInput.classList.contains('hidden')) {
                deadlineInput.focus();
            }
            return;
        }

        if (link && link.dataset.action === 'open-color-picker') {
            e.preventDefault();
            const palette = link.nextElementSibling;
            palette.classList.toggle('hidden');
            return;
        }

        if (target.closest('.color-option')) {
            const option = target.closest('.color-option');
            const color = option.dataset.color;
            const itemLi = option.closest('.p-3');
            const itemId = itemLi.querySelector('[data-item-id]').dataset.itemId;

            try {
                await api.put(`${baseUrl}/items/${itemId}`, { color });
                const list = lists.find(l => l.items.some(i => i.id == itemId));
                if (list) {
                    list.items.find(i => i.id == itemId).color = color;
                }
                renderTasks();
            } catch (error) {
                console.error('Failed to update item color:', error);
            }
            return;
        }

        if (target.classList.contains('item-title')) {
            const span = target;
            const input = span.nextElementSibling;
            span.classList.add('hidden');
            input.classList.remove('hidden');
            input.focus();
            input.select();
            return;
        }

        if (button && button.dataset.action === 'delete-item') {
            const itemId = button.dataset.itemId;
            try {
                await api.delete(`${baseUrl}/items/${itemId}`);
                const list = lists.find(l => l.id == activeListId);
                if (list) {
                    list.items = list.items.filter(item => item.id != itemId);
                }
                renderTasks();
            } catch (error) {
                console.error('Failed to delete item:', error);
            }
        }
    });

    listsContainer.addEventListener('change', async (e) => {
        if (e.target.dataset.action === 'update-deadline') {
            const input = e.target;
            const itemId = input.dataset.itemId;
            const deadline = input.value;
            try {
                await api.put(`${baseUrl}/items/${itemId}`, { deadline });
                const list = lists.find(l => l.items.some(i => i.id == itemId));
                if (list) {
                    list.items.find(i => i.id == itemId).deadline = deadline;
                }
                renderTasks();
            } catch (error) {
                console.error('Failed to update item deadline:', error);
            }
            return;
        }

        if (e.target.dataset.action === 'toggle-item') {
            const checkbox = e.target;
            const itemId = checkbox.dataset.itemId;
            const completed = checkbox.checked;
            try {
                await api.put(`${baseUrl}/items/${itemId}`, { completed });
                const list = lists.find(l => l.items.some(i => i.id == itemId));
                if (list) {
                    list.items.find(i => i.id == itemId).completed = completed;
                }
                renderTasks();
            } catch (error) {
                console.error('Failed to update item:', error);
                checkbox.checked = !completed; // Revert on failure
            }
        }
    });

    listsContainer.addEventListener('focusout', (e) => {
        if (e.target.classList.contains('item-title-input')) {
            updateItemTitle(e.target);
        }
        if (e.target.classList.contains('item-deadline-input')) {
            e.target.classList.add('hidden');
        }
    });

    listsContainer.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target.classList.contains('item-title-input')) {
            updateItemTitle(e.target);
        } else if (e.key === 'Escape' && e.target.classList.contains('item-title-input')) {
            renderTasks(); // Just re-render to cancel
        }
    });

    addItemButton.addEventListener('click', addItem);
    newItemTitleInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            addItem();
        }
    });

    const initialize = async () => {
        if (lists.length === 0) {
            try {
                const newList = await api.post(storeUrl, { title: 'My To Do List' });
                lists.push(newList);
                activeListId = newList.id;
            } catch (error) {
                console.error('Failed to create initial list:', error);
                listsContainer.innerHTML = '<p class="text-red-500 text-center">Could not initialize to-do list.</p>';
                return;
            }
        }
        renderTasks();
    };

    initialize();
});
</script>
@endpush
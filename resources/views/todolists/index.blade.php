@extends('layouts.app')

@section('title', 'Free To-Do List Tool')

@section('header-title')
    Free Todo List Tool (with Email Notifications)
@endsection

@section('content')
{{-- Safelist for Tailwind CSS JIT compiler --}}
{{--
    bg-gray-100 bg-red-100 bg-yellow-100 bg-green-100 bg-blue-100 bg-indigo-100 bg-purple-100 bg-pink-100
    bg-gray-500 bg-red-500 bg-yellow-500 bg-green-500 bg-blue-500 bg-indigo-500 bg-purple-500 bg-pink-500
--}}
<div id="todo-app-container" class="container mx-auto px-4 py-8" data-lists="{{ json_encode($lists) }}" data-store-url="{{ route('todolists.store') }}" data-base-url="{{ url('/free-todo-list-tool') }}" data-csrf-token="{{ csrf_token() }}">
    <div class="max-w-3xl mx-auto">
        <!-- Create New List Form -->
        <div class="flex flex-col sm:flex-row sm:gap-2 p-1 rounded-lg mb-4">
             
        <div class="w-3/4">    
             <input type="text" id="new-list-title-input" placeholder="Enter new list title..." class="w-full px-4 py-1 text-sm border border-gray-300 rounded-md focus:outline-none  placeholder-gray-500 placeholder:text-sm">
        </div>
        
        <div class="w-1/4">
            <button id="add-list-button" class="sm:mt-2 md:mt-0 lg:mt-0 border border-sky-500 bg-white text-sky-500 font-semibold py-1 text-sm px-4 rounded-md hover:bg-sky-200 hover:text-black transition">
            <div class="flex flex-row gap-1 items-center">
            <div>
                <svg class="w-4 fill-sky-500"  viewBox="0 0 256 256" id="Flat" xmlns="http://www.w3.org/2000/svg"><g id="SVGRepo_bgCarrier" stroke-width="0"></g><g id="SVGRepo_tracerCarrier" stroke-linecap="round" stroke-linejoin="round"></g><g id="SVGRepo_iconCarrier"> <path d="M28,64A12,12,0,0,1,40,52H216a12,12,0,0,1,0,24H40A12,12,0,0,1,28,64Zm12,76H216a12,12,0,0,0,0-24H40a12,12,0,0,0,0,24Zm104,40H40a12,12,0,0,0,0,24H144a12,12,0,0,0,0-24Zm88,0H220V168a12,12,0,0,0-24,0v12H184a12,12,0,0,0,0,24h12v12a12,12,0,0,0,24,0V204h12a12,12,0,0,0,0-24Z"></path> </g></svg>
            </div>
            <div>Create new list</div>
                </div>
            </button>
        </div>
             
        </div>

        <!-- List Pills -->
        <div id="list-pills-container" class="flex flex-wrap gap-2 mb-4">
            <!-- Pills will be rendered here -->
        </div>

        <!-- To-Do Lists Container -->
        <div id="todo-lists-container" class="space-y-4">
            <!-- Lists will be rendered here by JavaScript -->
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const appContainer = document.getElementById('todo-app-container');
    const storeUrl = appContainer.dataset.storeUrl;
    const baseUrl = appContainer.dataset.baseUrl;
    const csrfToken = appContainer.dataset.csrfToken;
    let lists = JSON.parse(appContainer.dataset.lists);
    let activeListId = lists.length > 0 ? lists[0].id : null;

    const newListTitleInput = document.getElementById('new-list-title-input');
    const addListButton = document.getElementById('add-list-button');
    const listsContainer = document.getElementById('todo-lists-container');
    const pillsContainer = document.getElementById('list-pills-container');

    const colors = {
        'gray': 'Gray',
        'red': 'Red',
        'yellow': 'Yellow',
        'green': 'Green',
        'blue': 'Blue',
        'indigo': 'Indigo',
        'purple': 'Purple',
        'pink': 'Pink'
    };

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

    const formatDeadline = (deadline) => {
        if (!deadline) return '';
        const date = new Date(deadline);
        const options = { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true };
        return new Intl.DateTimeFormat('en-US', options).format(date);
    };

    const renderPills = () => {
        pillsContainer.innerHTML = '';
        lists.forEach(list => {
            const pill = document.createElement('button');
            pill.className = `px-3 py-1 rounded-full text-sm transition ${
                list.id == activeListId
                    ? 'bg-blue-500 text-white'
                    : 'bg-gray-200 text-gray-700 hover:bg-gray-300'
            }`;
            pill.textContent = list.title;
            pill.dataset.listId = list.id;
            pill.addEventListener('click', () => {
                activeListId = list.id;
                renderPills();
                renderLists();
            });
            pillsContainer.appendChild(pill);
        });
    };

    const renderLists = () => {
        listsContainer.innerHTML = '';

        let orderedLists = [...lists];
        if (activeListId) {
            const activeList = orderedLists.find(l => l.id == activeListId);
            if (activeList) {
                orderedLists = [activeList, ...orderedLists.filter(l => l.id != activeListId)];
            }
        }

        orderedLists.forEach(list => {
            const listElement = document.createElement('div');
            listElement.className = 'bg-white p-4 rounded-lg shadow-md';
            listElement.id = `list-${list.id}`;
            listElement.innerHTML = `
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-700">${list.title}</h2>
                    <button data-action="delete-list" data-list-id="${list.id}" class="text-gray-400 hover:text-red-500 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
                <div class="flex flex-wrap gap-2 mb-4">
                    <input type="text" data-list-id="${list.id}" placeholder="Add a new task..." class="new-item-title-input flex-grow px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder-gray-500">
                    <div class="relative color-picker" data-list-id="${list.id}" data-selected-color="gray">
                        <button type="button" class="color-picker-button flex items-center bg-white border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 p-2">
                            <span class="color-swatch w-4 h-4 rounded-full bg-gray-500"></span>
                        </button>
                        <div class="color-palette absolute z-10 mt-2 w-40 bg-white border border-gray-200 rounded-md shadow-lg hidden">
                            ${Object.entries(colors).map(([value, name]) => `
                                <div class="color-option flex items-center gap-2 p-2 cursor-pointer hover:bg-gray-200" data-color="${value}">
                                    <span class="w-4 h-4 rounded-full bg-${value}-500"></span>
                                    <span>${name}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <input type="text" data-list-id="${list.id}" class="new-item-deadline-input bg-white border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder-gray-500 py-2" placeholder="Set a deadline">
                    <button data-action="add-item" data-list-id="${list.id}" class="bg-gray-200 text-gray-700 font-semibold py-2 px-3 rounded-md hover:bg-gray-300 text-sm transition">Add</button>
                </div>
                <ul class="space-y-2">
                    ${(list.items || []).map(item => `
                        <li class="p-2 rounded-md bg-${item.color}-100" id="item-${item.id}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input type="checkbox" data-action="toggle-item" data-item-id="${item.id}" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" ${item.completed ? 'checked' : ''}>
                                    <span class="ml-3 text-sm text-gray-800 ${item.completed ? 'line-through text-gray-500' : ''}">${item.title}</span>
                                </div>
                                <button data-action="delete-item" data-list-id="${list.id}" data-item-id="${item.id}" class="text-gray-400 hover:text-red-500 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <div class="flex items-center justify-between mt-1 ml-7">
                                <div class="flex items-center gap-2">
                                    <div class="relative item-color-picker" data-item-id="${item.id}" data-selected-color="${item.color}">
                                        <button type="button" class="item-color-picker-button flex items-center gap-1 p-1 bg-gray-50 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            <span class="color-swatch w-4 h-4 rounded-full bg-${item.color}-500"></span>
                                        </button>
                                        <div class="item-color-palette absolute z-10 mt-2 w-40 bg-white border border-gray-200 rounded-md shadow-lg hidden left-0">
                                            ${Object.entries(colors).map(([value, name]) => `
                                                <div class="color-option flex items-center gap-2 p-2 cursor-pointer hover:bg-gray-100" data-color="${value}">
                                                    <span class="w-4 h-4 rounded-full bg-${value}-500"></span>
                                                    <span>${name}</span>
                                                </div>
                                            `).join('')}
                                        </div>
                                    </div>
                                    <div class="deadline-container relative">
                                        ${item.deadline ?
                                            `<a href="#" class="deadline-link text-xs text-gray-500 hover:underline" data-item-id="${item.id}">Complete by: ${formatDeadline(item.deadline)}</a>` :
                                            `<a href="#" class="deadline-link text-xs text-blue-500 hover:underline" data-item-id="${item.id}">Set a Deadline</a>`
                                        }
                                        <input type="datetime-local" data-action="update-deadline" data-item-id="${item.id}" class="item-deadline-input bg-white border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 hidden" value="${item.deadline ? new Date(item.deadline).toISOString().slice(0, 16) : ''}" title="Set a new deadline">
                                    </div>
                                </div>
                            </div>
                        </li>
                    `).join('')}
                </ul>
            `;
            listsContainer.appendChild(listElement);

            const deadlineInput = listElement.querySelector('.new-item-deadline-input');
            deadlineInput.addEventListener('focus', (e) => {
                e.target.type = 'datetime-local';
            });
            deadlineInput.addEventListener('blur', (e) => {
                if (!e.target.value) {
                    e.target.type = 'text';
                }
            });

        });
    };

    const addList = async () => {
        const title = newListTitleInput.value.trim();
        if (title === '') return;
        try {
            const newList = await api.post(storeUrl, { title });
            lists.push(newList);
            newListTitleInput.value = '';
            activeListId = newList.id;
            renderPills();
            renderLists();
        } catch (error) {
            console.error('Failed to add list:', error);
        }
    };

    listsContainer.addEventListener('click', async (e) => {
        const target = e.target;
        const button = target.closest('button');

        if (target.classList.contains('deadline-link')) {
            e.preventDefault();
            const container = target.closest('.deadline-container');
            const input = container.querySelector('.item-deadline-input');
            target.classList.add('hidden');
            input.classList.remove('hidden');
            input.focus();
            return;
        }

        if (button && (button.classList.contains('color-picker-button') || button.classList.contains('item-color-picker-button'))) {
            const palette = button.nextElementSibling;
            const isHidden = palette.classList.contains('hidden');
            
            // Close all palettes
            document.querySelectorAll('.color-palette, .item-color-palette').forEach(p => p.classList.add('hidden'));
            
            // If the clicked one was hidden, show it
            if (isHidden) {
                palette.classList.remove('hidden');
            }
            return;
        }

        if (target.closest('.color-option')) {
            const option = target.closest('.color-option');
            const color = option.dataset.color;
            const picker = option.closest('.color-picker') || option.closest('.item-color-picker');
            
            if (picker.classList.contains('item-color-picker')) {
                // Handle item color update
                const itemId = picker.dataset.itemId;
                try {
                    await api.put(`${baseUrl}/items/${itemId}`, { color });
                    const list = lists.find(l => l.items.some(i => i.id == itemId));
                    if (list) {
                        const item = list.items.find(i => i.id == itemId);
                        item.color = color;
                    }
                    renderLists();
                } catch (error) {
                    console.error('Failed to update item color:', error);
                }
            } else {
                // Handle new item color selection
                picker.dataset.selectedColor = color;
                const button = picker.querySelector('.color-picker-button');
                button.querySelector('.color-swatch').className = `color-swatch w-4 h-4 rounded-full bg-${color}-500`;
                picker.querySelector('.color-palette').classList.add('hidden');
            }
            return;
        }

        if (!button) return;

        const action = button.dataset.action;
        const listId = button.dataset.listId;
        const itemId = button.dataset.itemId;

        if (action === 'delete-list') {
            try {
                await api.delete(`${baseUrl}/${listId}`);
                lists = lists.filter(list => list.id != listId);
                if (activeListId == listId) {
                    activeListId = lists.length > 0 ? lists[0].id : null;
                }
                renderPills();
                renderLists();
            } catch (error) {
                console.error('Failed to delete list:', error);
            }
        }

        if (action === 'add-item') {
            const input = listsContainer.querySelector(`.new-item-title-input[data-list-id="${listId}"]`);
            const colorPicker = listsContainer.querySelector(`.color-picker[data-list-id="${listId}"]`);
            const deadlineInput = listsContainer.querySelector(`.new-item-deadline-input[data-list-id="${listId}"]`);
            const title = input.value.trim();
            const color = colorPicker.dataset.selectedColor;
            const deadline = deadlineInput.value;
            if (title === '') return;
            try {
                const newItem = await api.post(`${baseUrl}/${listId}/items`, { title, color, deadline });
                const list = lists.find(l => l.id == listId);
                if (list) {
                    list.items = list.items || [];
                    list.items.push(newItem);
                }
                renderLists();
            } catch (error) {
                console.error('Failed to add item:', error);
            }
        }

        if (action === 'delete-item') {
            try {
                await api.delete(`${baseUrl}/items/${itemId}`);
                const list = lists.find(l => l.id == listId);
                if (list) {
                    list.items = list.items.filter(item => item.id != itemId);
                }
                renderLists();
            } catch (error) {
                console.error('Failed to delete item:', error);
            }
        }
    });

    listsContainer.addEventListener('change', async (e) => {
        const target = e.target;
        const action = target.dataset.action;
        const itemId = target.dataset.itemId;

        if (action === 'toggle-item') {
            const completed = target.checked;
            try {
                await api.put(`${baseUrl}/items/${itemId}`, { completed });
                const list = lists.find(l => l.items.some(i => i.id == itemId));
                if (list) {
                    const item = list.items.find(i => i.id == itemId);
                    item.completed = completed;
                }
                renderLists();
            } catch (error) {
                console.error('Failed to update item:', error);
                target.checked = !completed; // Revert on failure
            }
        }

        if (action === 'update-deadline') {
            const deadline = target.value;
            try {
                await api.put(`${baseUrl}/items/${itemId}`, { deadline });
                const list = lists.find(l => l.items.some(i => i.id == itemId));
                if (list) {
                    const item = list.items.find(i => i.id == itemId);
                    item.deadline = deadline;
                }
                renderLists();
            } catch (error) {
                console.error('Failed to update deadline:', error);
            }
        }
    });

    listsContainer.addEventListener('blur', (e) => {
        if (e.target.classList.contains('item-deadline-input')) {
            const container = e.target.closest('.deadline-container');
            const link = container.querySelector('.deadline-link');
            e.target.classList.add('hidden');
            link.classList.remove('hidden');
        }
    }, true);

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.color-picker') && !e.target.closest('.item-color-picker')) {
            document.querySelectorAll('.color-palette, .item-color-palette').forEach(palette => {
                palette.classList.add('hidden');
            });
        }
    });
    
    addListButton.addEventListener('click', addList);
    newListTitleInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            addList();
        }
    });

    listsContainer.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target.classList.contains('new-item-title-input')) {
            const listId = e.target.dataset.listId;
            const addButton = listsContainer.querySelector(`button[data-action="add-item"][data-list-id="${listId}"]`);
            if (addButton) {
                addButton.click();
            }
        }
    });

    renderPills();
    renderLists();
});
</script>
@endpush
@endsection

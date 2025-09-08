@extends('layouts.app')

@section('title', 'Free To-Do List Tool')

@section('header-title')
    Free Todo List Tool (with Email Notifications)
@endsection

@section('content')
{{-- Safelist for Tailwind CSS JIT compiler --}}
{{--
    bg-gray-100 bg-red-100 bg-yellow-100 bg-green-100 bg-blue-100 bg-indigo-100 bg-purple-100 bg-pink-100
    bg-gray-400 bg-red-400 bg-yellow-400 bg-green-400 bg-blue-400 bg-indigo-400 bg-purple-400 bg-pink-400
--}}
<div id="todo-app-container" class="container mx-auto px-4 py-8" data-lists="{{ json_encode($lists) }}" data-store-url="{{ route('todolists.store') }}" data-base-url="{{ url('/free-todo-list-tool') }}" data-csrf-token="{{ csrf_token() }}">
    <div class="max-w-3xl mx-auto">
        <!-- Create New List Form -->
        <div class="bg-white p-4 rounded-lg mb-6">
            <div class="flex space-x-2">
                <input type="text" id="new-list-title-input" placeholder="Enter new list title..." class="w-3/4 p-1 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button id="add-list-button" class="w-1/4 bg-transparent text-blue-500 font-semibold p-1 border border-blue-500 rounded-md hover:bg-blue-500 hover:text-white transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Create New List
                </button>
            </div>
        </div>

        <!-- List Pills -->
        <div id="list-pills-container" class="flex flex-wrap gap-2 mb-4">
            <!-- Pills will be rendered here -->
        </div>

        <!-- Priority Filter Pills -->
        <div id="priority-filter-container" class="flex flex-wrap gap-2 mb-4">
            <!-- Priority filters will be rendered here -->
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
    let activePriorityFilter = null;

    const newListTitleInput = document.getElementById('new-list-title-input');
    const addListButton = document.getElementById('add-list-button');
    const listsContainer = document.getElementById('todo-lists-container');
    const pillsContainer = document.getElementById('list-pills-container');
    const priorityFilterContainer = document.getElementById('priority-filter-container');

    const colors = {
        'red': '1',
        'yellow': '2',
        'purple': '3',
        'blue': '4',
        'indigo': '5',
        'green': '6',
        'pink': '7',
        'gray': '8'
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

    const renderPriorityFilters = () => {
        priorityFilterContainer.innerHTML = '';

        const activeList = lists.find(l => l.id == activeListId);
        const items = activeList ? activeList.items || [] : [];

        const allPrioritiesButton = document.createElement('button');
        allPrioritiesButton.textContent = `All Priorities (${items.length})`;
        allPrioritiesButton.className = `px-3 py-1 rounded-md text-sm transition ${
            activePriorityFilter === null
                ? 'bg-blue-500 text-white'
                : 'bg-gray-50 text-gray-700 hover:bg-gray-200'
        }`;
        allPrioritiesButton.addEventListener('click', () => {
            activePriorityFilter = null;
            renderPriorityFilters();
            renderLists();
        });
        priorityFilterContainer.appendChild(allPrioritiesButton);

        Object.entries(colors).forEach(([color, name]) => {
            const count = items.filter(item => item.color === color).length;
            const button = document.createElement('button');
            button.textContent = `Priority ${name} (${count})`;
            button.className = `px-3 py-1 rounded-md text-sm transition ${
                activePriorityFilter === color
                    ? 'bg-blue-500 text-white'
                    : 'bg-gray-50 text-gray-700 hover:bg-gray-200'
            }`;
            button.addEventListener('click', () => {
                activePriorityFilter = color;
                renderPriorityFilters();
                renderLists();
            });
            priorityFilterContainer.appendChild(button);
        });
    };

    const formatDate = (dateString) => {
        const options = { day: 'numeric', month: 'short', year: 'numeric' };
        return new Date(dateString).toLocaleDateString('en-US', options);
    };

    const formatDeadline = (deadline) => {
        if (!deadline) return 'Set a Deadline';
        const date = new Date(deadline);
        const options = { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: 'numeric', hour12: true };
        return date.toLocaleString('en-US', options);
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
            listElement.className = 'bg-white p-4 rounded-lg border border-dotted border-gray-300';
            listElement.id = `list-${list.id}`;
            listElement.innerHTML = `
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center">
                        <h2 class="text-xl font-bold text-gray-700">${list.title}</h2>
                        <span class="ml-3 text-xs text-gray-500">${formatDate(list.created_at)}</span>
                    </div>
                    <div class="flex items-center">
                        <a href="${baseUrl}/${list.id}/export" class="text-gray-400 hover:text-green-500 transition mr-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                        </a>
                        <button data-action="delete-list" data-list-id="${list.id}" class="text-gray-400 hover:text-red-500 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2 mb-4">
                    <input type="text" data-list-id="${list.id}" placeholder="Add a new task..." class="new-item-title-input flex-grow px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder-gray-500">
                    <div class="relative color-picker" data-list-id="${list.id}" data-selected-color="gray">
                        <button type="button" class="color-picker-button flex items-center gap-2 bg-white border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 px-3 py-2">
                            <span class="color-swatch w-4 h-4 rounded-full bg-gray-400"></span>
                            <span>Priority 8</span>
                        </button>
                        <div class="color-palette absolute z-10 mt-2 w-40 bg-white border border-gray-200 rounded-md shadow-lg hidden">
                            ${Object.entries(colors).map(([value, name]) => `
                                <div class="color-option flex items-center gap-2 p-2 cursor-pointer hover:bg-gray-100" data-color="${value}">
                                    <span class="w-4 h-4 rounded-full bg-${value}-400"></span>
                                    <span class="text-xs">Priority ${name}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                    <input type="text" data-list-id="${list.id}" class="new-item-deadline-input bg-white border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder-gray-500" placeholder="Set a deadline">
                    <button data-action="add-item" data-list-id="${list.id}" class="bg-gray-200 text-gray-700 font-semibold py-2 px-3 rounded-md hover:bg-gray-300 text-sm transition">Add</button>
                </div>
                <ul class="space-y-2">
                    ${(list.items || []).filter(item => !activePriorityFilter || item.color === activePriorityFilter).map(item => `
                        <li class="p-2 rounded-md bg-${item.color}-100" data-item-id="${item.id}">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <input type="checkbox" data-action="toggle-item" data-item-id="${item.id}" class="h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500" ${item.completed ? 'checked' : ''}>
                                    <span class="ml-3 text-sm text-gray-800 ${item.completed ? 'line-through text-gray-500' : ''}">${item.title}</span>
                                </div>
                                <button data-action="delete-item" data-list-id="${list.id}" data-item-id="${item.id}" class="text-gray-400 hover:text-red-500 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                            <div class="flex items-center gap-4 mt-2 pl-7">
                                <div class="relative item-color-picker">
                                    <a href="#" data-action="open-color-picker" class="text-xs text-gray-500 hover:underline">Priority ${colors[item.color]}</a>
                                    <div class="item-color-palette absolute z-10 mt-2 w-40 bg-white border border-gray-200 rounded-md shadow-lg hidden" style="left: 0;">
                                        ${Object.entries(colors).map(([value, name]) => `
                                            <div class="color-option flex items-center gap-2 p-2 cursor-pointer hover:bg-gray-100" data-color="${value}">
                                                <span class="w-4 h-4 rounded-full bg-${value}-400"></span>
                                                <span class="text-xs">Priority ${name}</span>
                                            </div>
                                        `).join('')}
                                    </div>
                                </div>
                                <div class="relative">
                                    <a href="#" data-action="open-deadline-picker" data-item-id="${item.id}" class="text-blue-500 hover:underline text-xs">${formatDeadline(item.deadline)}</a>
                                    <input type="datetime-local" data-action="update-deadline" data-item-id="${item.id}" class="item-deadline-input absolute right-0 top-full mt-2 z-10 bg-white border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-1 focus:ring-blue-500 placeholder-gray-500 hidden" value="${item.deadline ? new Date(item.deadline).toISOString().slice(0, 16) : ''}">
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
        const button = e.target.closest('button');
        const link = e.target.closest('a');

        if (button && button.dataset.action === 'delete-item') {
            const listId = button.dataset.listId;
            const itemId = button.dataset.itemId;
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
            return;
        }

        if (button && button.classList.contains('color-picker-button')) {
            const palette = button.nextElementSibling;
            palette.classList.toggle('hidden');
            return;
        }

        if (e.target.closest('.color-picker .color-option')) {
            const option = e.target.closest('.color-option');
            const color = option.dataset.color;
            const picker = option.closest('.color-picker');
            if (picker) {
                picker.dataset.selectedColor = color;
                
                const button = picker.querySelector('.color-picker-button');
                button.querySelector('.color-swatch').className = `color-swatch w-4 h-4 rounded-full bg-${color}-400`;
                button.querySelector('span:last-child').textContent = `Priority ${colors[color]}`;
                
                picker.querySelector('.color-palette').classList.add('hidden');
            }
            return;
        }

        if (link && link.dataset.action === 'open-color-picker') {
            e.preventDefault();
            const palette = link.nextElementSibling;
            palette.classList.toggle('hidden');
            return;
        }

        if (e.target.closest('.item-color-palette .color-option')) {
            const option = e.target.closest('.color-option');
            const color = option.dataset.color;
            const itemLi = option.closest('li');
            const itemId = itemLi.dataset.itemId;

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
            return;
        }

        if (link && link.dataset.action === 'open-deadline-picker') {
            e.preventDefault();
            const deadlineInput = link.nextElementSibling;
            deadlineInput.classList.toggle('hidden');
            if (!deadlineInput.classList.contains('hidden')) {
                deadlineInput.focus();
            }
            return;
        }
 
        if (!button) return;
 
        const action = button.dataset.action;
        const listId = button.dataset.listId;
        const itemId = button.dataset.itemId;

        if (action === 'delete-list') {
            if (confirm('Are you sure you want to delete this list?')) {
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

    });

    listsContainer.addEventListener('change', async (e) => {
        if (e.target.dataset.action === 'toggle-item') {
            const checkbox = e.target;
            const itemId = checkbox.dataset.itemId;
            const completed = checkbox.checked;
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
                checkbox.checked = !completed; // Revert on failure
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
                    const item = list.items.find(i => i.id == itemId);
                    item.deadline = deadline;
                }
                renderLists();
            } catch (error) {
                console.error('Failed to update item deadline:', error);
            }
        }
    });

    listsContainer.addEventListener('focusout', (e) => {
        if (e.target.classList.contains('item-deadline-input')) {
            e.target.classList.add('hidden');
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
    renderPriorityFilters();
    renderLists();

    document.addEventListener('click', (e) => {
        // Close new item color picker
        const newItemPicker = document.querySelector('.color-picker');
        if (newItemPicker && !newItemPicker.contains(e.target)) {
            newItemPicker.querySelector('.color-palette').classList.add('hidden');
        }

        // Close existing item color pickers
        const openItemPalettes = document.querySelectorAll('.item-color-palette:not(.hidden)');
        openItemPalettes.forEach(palette => {
            if (!palette.closest('.item-color-picker').contains(e.target)) {
                palette.classList.add('hidden');
            }
        });
    });
});
</script>
@endpush
@endsection
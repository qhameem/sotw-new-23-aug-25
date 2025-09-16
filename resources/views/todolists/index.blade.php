@extends('layouts.todolist')

@section('title', $meta_title)
@section('meta_description', $meta_description)

@section('content')
<div id="todo-app-container" class="bg-white rounded-lg shadow-md p-6 w-full max-w-2xl" data-lists="{{ json_encode($lists ?? []) }}" data-store-url="{{ route('todolists.store') }}" data-base-url="{{ url('/free-todo-list-tool') }}" data-csrf-token="{{ csrf_token() }}">
    <div class="flex justify-between items-center mb-6">
        <div class="relative">
            <button id="list-switcher-button" class="text-2xl font-bold text-gray-800 focus:outline-none flex items-center">
                <span id="active-list-title"></span>
                <svg class="w-6 h-6 ml-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>
            <div id="list-dropdown" class="absolute left-0 mt-2 w-56 bg-white rounded-md shadow-lg z-10 hidden">
                <!-- List items will be populated here -->
            </div>
        </div>
        <button id="create-new-list-button" class="text-sky-500 px-4 py-2 rounded-lg hover:bg-sky-50 transition-colors text-sm font-medium">&oplus; New List</button>
    </div>
    <h1 id="list-title-container" class="text-2xl font-bold text-gray-800 mb-6 hidden" data-list-id="">
        <span id="list-title" class="cursor-pointer"></span>
        <input type="text" id="list-title-input" class="text-2xl font-bold text-gray-800 border-b border-gray-300 focus:outline-none hidden w-full">
    </h1>

    <!-- Priority Filter Tags -->
    <div id="priority-filter-container" class="flex items-center gap-2 mb-4">
        <!-- Tags will be dynamically inserted here -->
    </div>

    <!-- Add New Task -->
    <div class="flex flex-col sm:flex-row gap-2 mb-6">
        <input type="text" id="new-item-title-input" placeholder="Add new task" class="flex-grow px-1 py-2 bg-transparent border-0 border-b border-sky-300 focus:outline-none focus:ring-0 focus:border-sky-500 placeholder-gray-400 placeholder:text-sm">
        <button id="add-item-button" class="border border-sky-300 text-sky-500 font-bold w-full sm:w-10 h-10 flex items-center justify-center rounded-lg hover:bg-sky-50 transition-colors flex-shrink-0">
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

<footer class="text-center mt-8">
    <p class="text-xs text-gray-400">
        A free Todo list tool by
        <a href="{{ route('home') }}" class="underline hover:text-gray-600">
            Software on the Web
        </a>
    </p>
</footer>

<div class="mt-12 text-left text-gray-600 max-w-2xl mx-auto prose text-sm">
    <h2 class="text-regular font-medium">Why We Built This Todo List (And Why You'll Actually Want to Use It)</h2>
    <br>
    <p>Look, I've tried them all. Todoist's free version barely lets you organize anything. Any.do looks pretty but good luck getting reminders without paying. Don't even get me started on how Wunderlist disappeared and Microsoft To Do still feels like a downgrade.</p>
    <br>
    <p>The problem with most "free" todo apps? They give you just enough to get hooked, then hit you with a paywall the moment you need something useful. Want email reminders? That'll be $5 a month. Need to color-code your tasks? Premium feature. Multiple lists? Sorry, free users get one.</p>
    <br>
    <p>That's exactly why we made this. A todo list that actually works without constantly asking for your credit card.</p>
    <br>

    <h2 class="text-regular font-medium">What You Get (No Strings Attached)</h2>
    <br>
    <h3 class="text-black">Multiple Lists & Organization</h3>
    <br>
    <p>Create separate lists for work, personal, shopping, or whatever chaos you're managing. Most free apps limit you to one list or a handful of tasks.</p>
    <br>
    <h3 class="text-black">Priority Colors That Actually Help</h3>
    <br>
    <p>Red for urgent, yellow for important, green for whenever-you-get-to-it. Visual priority sorting that doesn't require a subscription.</p>
    <br>
    <h3 class="text-black">Real Email Notifications</h3>
    <br>
    <p>Here's the big one - you get actual email alerts when deadlines approach or pass. Not just phone notifications you'll ignore, but emails that land in your inbox. Most apps either don't offer this for free users or send you promotional emails disguised as "notifications."</p>
    <br>

    <h3 class="text-black">Smart Filtering</h3>
    <br>
    <p>Click to see only high-priority items, or filter by what's due today. Basic functionality that somehow became a premium feature elsewhere.</p>
    <br>
    <h3 class="text-black">Deadline Management</h3>
    <p>Set due dates and times without hitting upgrade walls. Revolutionary concept, right?</p>
    <br>
    <h2 class="text-regular font-medium">Questions People Actually Ask</h2><br>
    <h3 class="text-black">Do you really send email notifications for free?</h3><br>
    <p>Yes. Real deadline alerts to your actual email address. No upgrade required, no limited number of notifications, no catch. This seems to shock people because apparently basic functionality is now considered premium.</p>
    <br>
    <h3 class="text-black">How many lists can I create?</h3><br>
    <p>As many as you want. We don't artificially limit this to push you toward paid plans.</p>
    <br>
    <h3 class="text-black">Can I share lists with other people?</h3><br>
    <p>Not yet, but it's coming. Unlike other apps, when we add sharing, it'll be free too.</p>
    <br>
    <h3 class="text-black">Will this stay free or become another bait-and-switch?</h3><br>
    <p>The core features stay free. We're not trying to build a subscription empire here - just a todo list that works like todo lists should work.</p><br>

    <h3 class="text-black">What about mobile apps?</h3><br>
    <p>Works great in your phone's browser. We know everyone wants native apps, but honestly, the web version does everything you need without taking up storage space.</p>
    <br>
    <h3 class="text-black">Do you sell my data or send spam?</h3><br>
    <p>No data selling. The only emails you'll get are the deadline notifications you asked for. We're not trying to become your new newsletter subscription.</p>
    <br>
    <h2 class="text-regular font-medium">The Bottom Line</h2><br>
    <p>This exists because we got tired of todo apps that treat basic organization features like luxury add-ons. Email notifications shouldn't cost $60 a year. Color-coding tasks shouldn't require a premium subscription.</p><br>
    <p>If you've bounced between Todoist, Any.do, Microsoft To Do, and TickTick looking for something that just works without the constant upgrade prompts, this might be what you've been looking for.</p><br>
    <p>Free means free. Working means working. Email notifications included.</p><br>
    <hr>
    <p class="text-xs"><em>Built by some who actually uses todo lists and got fed up with the alternatives.</em></p>
</div>
@endsection

@push('scripts')
<style>
    .priority-tag {
        display: inline-flex;
        align-items: center;
        padding: 4px 12px;
        border-radius: 16px;
        font-size: 0.75rem; /* text-xs */
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        background-color: #f3f4f6; /* gray-100 */
        color: #4b5563; /* gray-600 */
    }
    .priority-tag.active {
        background-color: #e0f2fe; /* sky-100 */
        color: #075985; /* sky-800 */
        border: 1px solid #bae6fd; /* sky-200 */
        font-weight: 500;
    }
    .priority-tag .close-icon {
        margin-left: 8px;
        width: 16px;
        height: 16px;
        stroke: #9ca3af; /* gray-400 */
    }
    .priority-count {
        margin-left: 8px;
        min-width: 20px;
        height: 20px;
        border-radius: 50%;
        background-color: #e5e7eb; /* gray-200 */
        color: #4b5563; /* gray-600 */
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem; /* text-xs */
        font-weight: 500;
    }
    .priority-tag.active .priority-count {
        background-color: #bae6fd; /* sky-200 */
        color: #0c4a6e; /* sky-900 */
    }
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const appContainer = document.getElementById('todo-app-container');
    const storeUrl = appContainer.dataset.storeUrl;
    const baseUrl = appContainer.dataset.baseUrl;
    const csrfToken = appContainer.dataset.csrfToken;
    let lists = JSON.parse(appContainer.dataset.lists);
    let activeListId = lists.length > 0 ? lists[0].id : null;
    let activePriorityFilter = null;
 
    const newItemTitleInput = document.getElementById('new-item-title-input');
    const addItemButton = document.getElementById('add-item-button');
    const listsContainer = document.getElementById('todo-lists-container');
    const listTitleContainer = document.getElementById('list-title-container');
    const listTitle = document.getElementById('list-title');
    const listTitleInput = document.getElementById('list-title-input');
    const priorityFilterContainer = document.getElementById('priority-filter-container');
    const createNewListButton = document.getElementById('create-new-list-button');
    const listSwitcherButton = document.getElementById('list-switcher-button');
    const activeListTitle = document.getElementById('active-list-title');
    const listDropdown = document.getElementById('list-dropdown');

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

    const renderListTitle = () => {
        if (!activeListId) return;
        const list = lists.find(l => l.id == activeListId);
        if (list) {
            activeListTitle.textContent = list.title;
            listTitle.textContent = list.title;
            listTitleInput.value = list.title;
            listTitleContainer.dataset.listId = activeListId;
        }
    };

    const renderPriorityFilters = () => {
        const list = lists.find(l => l.id == activeListId);
        if (!list || !list.items) {
            priorityFilterContainer.innerHTML = '';
            return;
        }

        const priorityCounts = list.items.reduce((acc, item) => {
            const priority = item.color || 'gray';
            acc[priority] = (acc[priority] || 0) + 1;
            return acc;
        }, {});

        const priorities = [...new Set(list.items.map(item => item.color || 'gray'))];
        const priorityNames = { 'rose': 'Priority 1', 'orange': 'Priority 2', 'yellow': 'Priority 3', 'green': 'Priority 4', 'gray': 'Priority 5' };

        let filtersHtml = `<div class="priority-tag ${activePriorityFilter === null ? 'active' : ''}" data-priority="all"><span>All</span><span class="priority-count">${list.items.length}</span></div>`;

        filtersHtml += priorities.map(priority => `
            <div class="priority-tag ${activePriorityFilter === priority ? 'active' : ''}" data-priority="${priority}">
                <span>${priorityNames[priority] || priority}</span>
                <span class="priority-count">${priorityCounts[priority]}</span>
            </div>
        `).join('');
        priorityFilterContainer.innerHTML = filtersHtml;
    };


    const renderListsDropdown = () => {
        listDropdown.innerHTML = '';
        lists.forEach(list => {
            const listItemWrapper = document.createElement('div');
            listItemWrapper.className = 'flex items-center justify-between px-4 py-2 text-sm text-gray-700 hover:bg-gray-100';
            
            const listItem = document.createElement('a');
            listItem.href = '#';
            listItem.textContent = list.title;
            listItem.dataset.listId = list.id;
            listItem.className = 'flex-grow';
            
            const deleteButton = document.createElement('button');
            deleteButton.innerHTML = '<svg class="w-4 h-4 text-gray-400 hover:text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
            deleteButton.dataset.listId = list.id;
            deleteButton.dataset.action = 'delete-list';
            deleteButton.className = 'ml-2';

            listItemWrapper.appendChild(listItem);
            listItemWrapper.appendChild(deleteButton);
            listDropdown.appendChild(listItemWrapper);
        });
    };

    const renderTasks = () => {
        listsContainer.innerHTML = '';
        if (!activeListId) return;
 
        renderListTitle();
        renderPriorityFilters();
 
        const list = lists.find(l => l.id == activeListId);
        if (!list || !list.items) return;
 
        const priorityOrder = { 'rose': 1, 'orange': 2, 'yellow': 3, 'green': 4, 'gray': 5 };

        const sortedItems = list.items.sort((a, b) => {
            const priorityA = priorityOrder[a.color || 'gray'] || 99;
            const priorityB = priorityOrder[b.color || 'gray'] || 99;
            return priorityA - priorityB;
        });

        const filteredItems = activePriorityFilter
            ? sortedItems.filter(item => (item.color || 'gray') === activePriorityFilter)
            : sortedItems;

        filteredItems.forEach(item => {
            const taskElement = document.createElement('div');
            const priorityColor = item.color || 'gray';
            taskElement.className = `p-3 border border-gray-200 rounded-lg bg-${priorityColor}-100`;
            
            const deadlineDate = item.deadline ? new Date(item.deadline) : null;
            const formattedDeadline = deadlineDate ? deadlineDate.toLocaleString('en-US', { month: 'short', day: 'numeric', hour: 'numeric', minute: '2-digit', hour12: true }) : 'Set Deadline';
            const deadlineColorClass = deadlineDate && deadlineDate < new Date() ? 'text-red-500' : 'text-gray-500';

            const priorityColors = {
                'rose': 1, 'orange': 2, 'yellow': 3, 'green': 4, 'gray': 5
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

    const updateListTitle = async () => {
        const newTitle = listTitleInput.value.trim();
        const listId = listTitleContainer.dataset.listId;
        const originalTitle = listTitle.textContent;

        if (newTitle === '' || newTitle === originalTitle) {
            listTitleInput.classList.add('hidden');
            listTitle.classList.remove('hidden');
            return;
        }

        try {
            await api.put(`${baseUrl}/${listId}`, { title: newTitle });
            const list = lists.find(l => l.id == listId);
            if (list) {
                list.title = newTitle;
            }
            renderListTitle();
        } catch (error) {
            console.error('Failed to update list title:', error);
            renderListTitle(); // Re-render to show original state
        } finally {
            listTitleInput.classList.add('hidden');
            listTitle.classList.remove('hidden');
        }
    };

    listTitle.addEventListener('click', () => {
        listTitle.classList.add('hidden');
        listTitleInput.classList.remove('hidden');
        listTitleInput.focus();
        listTitleInput.select();
    });

    listTitleInput.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            updateListTitle();
        } else if (e.key === 'Escape') {
            listTitleInput.classList.add('hidden');
            listTitle.classList.remove('hidden');
            renderListTitle();
        }
    });

    listTitleInput.addEventListener('focusout', updateListTitle);

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

    listDropdown.addEventListener('click', async (e) => {
        if (e.target.dataset.action === 'delete-list') {
            e.stopPropagation();
            const listId = e.target.dataset.listId;
            if (confirm('Are you sure you want to delete this list?')) {
                try {
                    const response = await api.delete(`${baseUrl}/${listId}`);
                    lists = response.lists;
                    if (activeListId == listId) {
                        activeListId = lists.length > 0 ? lists[0].id : null;
                    }
                    if (lists.length === 0) {
                        await initialize();
                    } else {
                        renderTasks();
                    }
                    listDropdown.classList.add('hidden');
                } catch (error) {
                    console.error('Failed to delete list:', error);
                }
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

    priorityFilterContainer.addEventListener('click', (e) => {
        const tag = e.target.closest('.priority-tag');
        if (!tag) return;

        const priority = tag.dataset.priority;

        if (priority === 'all') {
            activePriorityFilter = null;
        } else if (activePriorityFilter === priority) {
            activePriorityFilter = null; // Deselect if clicking the active filter
        } else {
            activePriorityFilter = priority;
        }
        
        renderTasks();
    });

    listSwitcherButton.addEventListener('click', () => {
        renderListsDropdown();
        listDropdown.classList.toggle('hidden');
    });

    listDropdown.addEventListener('click', (e) => {
        if (e.target.tagName === 'A') {
            e.preventDefault();
            activeListId = e.target.dataset.listId;
            listDropdown.classList.add('hidden');
            renderTasks();
        }
    });

    createNewListButton.addEventListener('click', async () => {
        const newListName = prompt("Enter the name for the new list:", "New List");
        if (newListName) {
            try {
                const newList = await api.post(storeUrl, { title: newListName });
                lists.push(newList);
                activeListId = newList.id;
                renderTasks();
            } catch (error) {
                console.error('Failed to create new list:', error);
            }
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
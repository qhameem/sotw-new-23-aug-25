import { sharedTailwindConfig } from './tailwind.shared.js';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './resources/views/**/*.blade.php',
        '!./resources/views/admin/**/*.blade.php',
        '!./resources/views/layouts/admin.blade.php',
        '!./resources/views/layouts/submission.blade.php',
        '!./resources/views/layouts/todolist.blade.php',
        '!./resources/views/components/admin/**/*.blade.php',
        '!./resources/views/products/create.blade.php',
        '!./resources/views/products/partials/_form.blade.php',
        '!./resources/views/todolists/**/*.blade.php',
        './resources/js/components/**/*.vue',
        '!./resources/js/components/ProductSubmit.vue',
        '!./resources/js/components/product-submit/**/*.vue',
        '!./resources/js/components/TodoList.vue',
        '!./resources/js/components/TodoListApp.vue',
        '!./resources/js/components/TodoItem.vue',
        '!./resources/js/components/AddTaskForm.vue',
        '!./resources/js/components/ListDropdown.vue',
        '!./resources/js/components/PriorityFilter.vue',
        '!./resources/js/components/todo/**/*.vue',
        './resources/js/**/*.js',
    ],
    ...sharedTailwindConfig,
};

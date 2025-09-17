@component('mail::message')
# Reminder: Your Task Deadline is Approaching

Hi {{ $task->todoList->user->name }},

This is a reminder that the deadline for your task **"{{ $task->title }}"** is approaching.

**Deadline:** {{ \Carbon\Carbon::parse($task->deadline)->format('F j, Y, g:i A') }}

You can view your task by clicking the button below:

@component('mail::button', ['url' => route('todolists.index')])
View My To-Do List
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent
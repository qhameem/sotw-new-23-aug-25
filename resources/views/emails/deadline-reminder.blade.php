@component('mail::message')
# Reminder: Your Task Deadline is Approaching

Hi {{ $task->todoList->user->name }},

This is a reminder that the deadline for your task **"{{ $task->title }}"** is approaching.

**Deadline:** {{ \Carbon\Carbon::parse($task->deadline)->format('F j, Y, g:i A') }}

You can view your task by clicking the button below:

@component('mail::button', ['url' => app(\App\Support\ToolSettings::class)->url(\App\Support\ToolSettings::TODO_LIST_KEY)])
View My To-Do List
@endcomponent

Thanks,<br>
{{ config('app.name') }}
@endcomponent

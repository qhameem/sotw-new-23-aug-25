<?php

namespace App\Console\Commands;

use App\Jobs\SendDeadlineReminderEmail;
use App\Models\TodoListItem;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SendDeadlineReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminders:send-deadline';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send deadline reminders to users for tasks that are due soon.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = Carbon::now();
        $reminderTime = $now->copy()->addHours(5);

        $tasks = TodoListItem::where('deadline', '>', $now)
            ->where('deadline', '<=', $reminderTime)
            ->where('reminder_sent', false)
            ->with('todoList.user')
            ->get();

        foreach ($tasks as $task) {
            SendDeadlineReminderEmail::dispatch($task);
            $task->update(['reminder_sent' => true]);
        }

        $this->info('Deadline reminders sent successfully.');
        return 0;
    }
}
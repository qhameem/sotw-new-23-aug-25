<?php

namespace App\Notifications;

use App\Models\SystemErrorReport;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class SystemErrorReported extends Notification
{
    use Queueable;

    public function __construct(private readonly SystemErrorReport $report)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'System error: '.$this->report->summary,
            'link' => route('admin.errors.index', ['report' => $this->report->id]),
            'system_error_report_id' => $this->report->id,
            'severity' => $this->report->severity,
        ];
    }
}

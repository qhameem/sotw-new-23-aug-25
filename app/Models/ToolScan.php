<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ToolScan extends Model
{
    protected $fillable = [
        'tool_user_id',
        'tool_key',
        'result_token',
        'submitted_url',
        'normalized_url',
        'final_url',
        'final_host',
        'guest_hash',
        'launch_score',
        'seo_score',
        'ai_score',
        'trust_score',
        'passed_checks',
        'warning_checks',
        'failed_checks',
        'status_label',
        'save_to_history',
        'audit_payload',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'audit_payload' => 'array',
            'save_to_history' => 'boolean',
            'scanned_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(ToolUser::class, 'tool_user_id');
    }

    public function getRouteKeyName(): string
    {
        return 'result_token';
    }
}

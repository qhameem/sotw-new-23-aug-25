<?php

namespace App\Support;

use App\Models\ToolScan;
use App\Models\ToolUser;
use Illuminate\Http\Request;

class LaunchReadinessGuestSession
{
    public function hash(Request $request): string
    {
        return hash('sha256', implode('|', [
            (string) $request->ip(),
            (string) $request->userAgent(),
            (string) $request->session()->getId(),
        ]));
    }

    public function claimScansForUser(ToolUser $toolUser, Request $request): void
    {
        ToolScan::query()
            ->whereNull('tool_user_id')
            ->where('tool_key', ToolSettings::LAUNCH_READINESS_KEY)
            ->where('guest_hash', $this->hash($request))
            ->update([
                'tool_user_id' => $toolUser->id,
                'guest_hash' => null,
            ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Support\ToolSettings;
use Illuminate\Http\Request;

class PublicToolController extends Controller
{
    public function __invoke(Request $request, ToolSettings $settings, string $toolSlug)
    {
        foreach ([ToolSettings::TODO_LIST_KEY, ToolSettings::LAUNCH_READINESS_KEY] as $key) {
            if ($settings->isLegacySlug($key, $toolSlug)) {
                return redirect($settings->path($key), 301);
            }
            if ($settings->isCurrentSlug($key, $toolSlug)) {
                return $key === ToolSettings::TODO_LIST_KEY
                    ? app(TodoListController::class)->index()
                    : app(LaunchReadinessController::class)->index();
            }
        }
        abort(404);
    }
}

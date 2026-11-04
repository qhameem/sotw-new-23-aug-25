<?php

namespace App\Http\Controllers;

use App\Mail\SiteFeedback;
use App\Models\Feedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class FeedbackController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:suggestion,bug,other'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:254'],
            'website' => ['nullable', 'max:0'],
        ]);

        $user = $request->user();
        Feedback::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'contact_email' => $data['email'] ?? null,
            'type' => $data['type'],
            'message' => $data['message'],
        ]);

        try {
            Mail::to(config('agent_access.contact'))->send(new SiteFeedback(
                $data['type'], $data['message'], $data['email'] ?? $user?->email,
            ));
        } catch (\Throwable $exception) {
            report($exception);
        }

        return response()->json(['message' => 'Your feedback has been sent.']);
    }
}

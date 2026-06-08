<?php

namespace App\Services;

use App\Models\SearchLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SearchTrackingService
{
    public function __construct(
        protected RequestLocationService $locationService
    ) {
    }

    public function track(Request $request, string $term, string $source = 'global_search_modal'): ?SearchLog
    {
        $searchTerm = $this->normalizeTerm($term);

        if (mb_strlen($searchTerm) < 2) {
            return null;
        }

        $location = $this->locationService->resolve($request);
        $user = $request->user();
        $ipAddress = $location['ip_address'] ?? null;
        $dedupeWindowStart = Carbon::now()->subSeconds(15);

        $existing = SearchLog::query()
            ->where('search_term', $searchTerm)
            ->where('source', $source)
            ->where('created_at', '>=', $dedupeWindowStart)
            ->when(
                $user,
                fn ($query) => $query->where('user_id', $user->getKey()),
                function ($query) use ($ipAddress) {
                    $query->whereNull('user_id');

                    if ($ipAddress) {
                        $query->where('ip_address', $ipAddress);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                }
            )
            ->latest('id')
            ->first();

        if ($existing) {
            return $existing;
        }

        return SearchLog::query()->create([
            'user_id' => $user?->getKey(),
            'search_term' => $searchTerm,
            'source' => $source,
            'ip_address' => $ipAddress,
            'country_code' => $location['country_code'] ?? null,
            'country_name' => $location['country_name'] ?? null,
            'city' => $location['city'] ?? null,
            'user_agent' => $this->truncateUserAgent((string) $request->userAgent()),
        ]);
    }

    protected function normalizeTerm(string $term): string
    {
        $normalized = preg_replace('/\s+/u', ' ', trim($term));

        return mb_substr((string) $normalized, 0, 255);
    }

    protected function truncateUserAgent(string $userAgent): ?string
    {
        $normalized = trim($userAgent);

        if ($normalized === '') {
            return null;
        }

        return mb_substr($normalized, 0, 512);
    }
}

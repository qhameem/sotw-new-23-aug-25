<?php

namespace App\Http\Controllers;

use App\Models\Ad;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AdInteractionController extends Controller
{
    public function click(Ad $ad, Request $request): RedirectResponse
    {
        abort_unless($ad->target_url, 404);

        $ad->increment('clicks_count');

        return redirect()->away($this->appendUtmParameters(
            $ad->target_url,
            $request->string('zone')->toString() ?: 'ad-' . $ad->id,
            $ad->id
        ));
    }

    public function impression(Ad $ad): Response
    {
        $ad->increment('impressions_count');

        return response(base64_decode('R0lGODlhAQABAPAAAP///wAAACH5BAAAAAAALAAAAAABAAEAAAICRAEAOw==', true), 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }

    protected function appendUtmParameters(string $url, string $campaign, int $adId): string
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return $url;
        }

        $query = [];

        if (! empty($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        $query = array_merge($query, [
            'utm_source' => 'software-on-the-web',
            'utm_medium' => 'advertising',
            'utm_campaign' => $campaign,
            'utm_content' => 'ad-' . $adId,
        ]);

        $rebuilt = ($parts['scheme'] ?? 'https') . '://' . $parts['host'];

        if (isset($parts['port'])) {
            $rebuilt .= ':' . $parts['port'];
        }

        $rebuilt .= $parts['path'] ?? '';
        $rebuilt .= '?' . http_build_query($query);

        if (! empty($parts['fragment'])) {
            $rebuilt .= '#' . $parts['fragment'];
        }

        return $rebuilt;
    }
}

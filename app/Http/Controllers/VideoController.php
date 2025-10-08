<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class VideoController extends Controller
{
    public function fetch(Request $request)
    {
        $url = $request->input('url');
        if (!$url) {
            return response()->json(['error' => 'URL is required.'], 400);
        }

        $video = $this->getVideoDetails($url);

        if (!$video) {
            return response()->json(['error' => 'Could not extract video information from the provided URL.'], 400);
        }

        return response()->json([$video]);
    }

    private function getVideoDetails($url)
    {
        if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
            return $this->getYouTubeDetails($url);
        }
        if (strpos($url, 'vimeo.com') !== false) {
            return $this->getVimeoDetails($url);
        }
        if (strpos($url, 'reddit.com') !== false) {
            return $this->getRedditDetails($url);
        }
        if (strpos($url, 'tiktok.com') !== false) {
            return $this->getTikTokDetails($url);
        }
        if (strpos($url, 'facebook.com') !== false) {
            return $this->getFacebookDetails($url);
        }
        if (strpos($url, 'twitter.com') !== false || strpos($url, 'x.com') !== false) {
            return $this->getTwitterDetails($url);
        }
        return null;
    }

    private function getYouTubeDetails($url)
    {
        $videoId = $this->extractYouTubeVideoId($url);
        if (!$videoId) {
            return null;
        }

        return [
            'thumbnail_url' => "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg",
            'embed_url' => "https://www.youtube.com/embed/{$videoId}",
        ];
    }

    private function extractYouTubeVideoId($url)
    {
        $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|\S*?[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
        preg_match($pattern, $url, $matches);
        return $matches[1] ?? null;
    }

    private function getVimeoDetails($url)
    {
        $videoId = $this->extractVimeoVideoId($url);
        if (!$videoId) {
            return null;
        }

        return [
            'thumbnail_url' => "https://vumbnail.com/{$videoId}.jpg",
            'embed_url' => "https://player.vimeo.com/video/{$videoId}",
        ];
    }

    private function extractVimeoVideoId($url)
    {
        $pattern = '/(?:https?:\/\/)?(?:www\.)?vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|)(\d+)/';
        preg_match($pattern, $url, $matches);
        return $matches[3] ?? null;
    }

    private function getRedditDetails($url)
    {
        // Ensure the URL ends with .json
        $jsonUrl = rtrim($url, '/') . '.json';

        try {
            $response = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
            ])->withoutVerifying()->get($jsonUrl);

            if ($response->successful()) {
                $data = $response->json();
                $postData = $data[0]['data']['children'][0]['data'] ?? null;

                if ($postData) {
                    $thumbnail = '';
                    // Prioritize video thumbnail if available
                    if (isset($postData['is_video']) && $postData['is_video'] && isset($postData['preview']['images'][0]['source']['url'])) {
                        $thumbnail = html_entity_decode($postData['preview']['images'][0]['source']['url']);
                    } elseif (isset($postData['thumbnail']) && filter_var($postData['thumbnail'], FILTER_VALIDATE_URL)) {
                        // Fallback to the main thumbnail if it's a valid URL
                        $thumbnail = $postData['thumbnail'];
                    }

                    $embedUrl = str_replace('.json', '', $jsonUrl);
                    $embedUrl = str_replace('//www.reddit.com', '//www.redditmedia.com', $embedUrl) . '?embed=true';

                    return [
                        'thumbnail_url' => $thumbnail,
                        'embed_url' => $embedUrl,
                    ];
                }
            } else {
                \Illuminate\Support\Facades\Log::error('Reddit JSON API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Reddit JSON API exception: ' . $e->getMessage());
        }

        return null; // Return null if video data can't be found
    }

    private function getTikTokDetails($url)
    {
        try {
            $response = Http::withoutVerifying()->get("https://www.tiktok.com/oembed?url={$url}");
            if ($response->successful()) {
                $data = $response->json();
                return [
                    'thumbnail_url' => $data['thumbnail_url'] ?? '',
                    'embed_url' => "https://www.tiktok.com/embed/v2/{$this->extractTikTokVideoId($url)}",
                ];
            }
        } catch (\Exception $e) {
            // Log error
        }
        return null;
    }

    private function extractTikTokVideoId($url)
    {
        $pattern = '/(?:https?:\/\/)?(?:www\.)?tiktok\.com\/@(?:[^\/]+)\/video\/(\d+)/';
        preg_match($pattern, $url, $matches);
        return $matches[1] ?? null;
    }

    private function getFacebookDetails($url)
    {
        return $this->getDetailsFromMicrolink($url);
    }

    private function getTwitterDetails($url)
    {
        return $this->getDetailsFromMicrolink($url);
    }

    private function getDetailsFromMicrolink($url)
    {
        try {
            $response = Http::withoutVerifying()->get('https://api.microlink.io', [
                'url' => $url,
                'video' => true,
            ]);

            if ($response->successful()) {
                $data = $response->json()['data'];
                $thumbnail = $data['image']['url'] ?? ($data['logo']['url'] ?? '');
                
                // Construct an embed URL. This is a generic approach and might need platform-specific logic.
                // For now, we'll just return the thumbnail and the original URL for embedding.
                // A more robust solution would determine the correct embed URL based on the platform.
                $embedUrl = $url; // Defaulting to the original URL

                return [
                    'thumbnail_url' => $thumbnail,
                    'embed_url' => $embedUrl,
                ];
            } else {
                \Illuminate\Support\Facades\Log::error('Microlink API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body()
                ]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Microlink API exception: ' . $e->getMessage());
        }

        return null;
    }
}
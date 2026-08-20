<?php

namespace App\Services;

use App\Models\NewsletterSubscriber;
use Illuminate\Http\Client\PendingRequest;
use RuntimeException;

class EmailOctopusNewsletterService
{
    public function subscribe(NewsletterSubscriber $localSubscriber): string
    {
        $apiKey = (string) config('services.emailoctopus.api_key');
        $listId = (string) config('services.emailoctopus.list_id');

        if ($apiKey === '' || $listId === '') {
            throw new RuntimeException('EmailOctopus API key or list ID is not configured.');
        }

        $payload = array_filter([
            'api_key' => $apiKey,
            'email_address' => $localSubscriber->email,
            'fields' => $localSubscriber->first_name
                ? ['FirstName' => $localSubscriber->first_name]
                : null,
        ], static fn ($value) => $value !== null);

        $response = $this->client()->post("lists/{$listId}/contacts", $payload);

        if ($response->successful()) {
            return (string) $response->json('id');
        }

        if ($response->status() === 409 || $response->json('error.code') === 'MEMBER_EXISTS_WITH_EMAIL_ADDRESS') {
            $memberId = md5(mb_strtolower($localSubscriber->email));
            $response = $this->client()->put("lists/{$listId}/contacts/{$memberId}", $payload);
        }

        $response->throw();

        $contactId = (string) $response->json('id');

        if ($contactId === '') {
            throw new RuntimeException('EmailOctopus returned an invalid contact ID.');
        }

        return $contactId;
    }

    private function client(): PendingRequest
    {
        return \Illuminate\Support\Facades\Http::baseUrl(rtrim((string) config('services.emailoctopus.base_url'), '/').'/')
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.emailoctopus.timeout', 10))
            ->retry(2, 250);
    }
}

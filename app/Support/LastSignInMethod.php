<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;

final class LastSignInMethod
{
    public const COOKIE = 'last_sign_in_method';

    public const EMAIL = 'email';

    public const GOOGLE = 'google';

    private const MINUTES_IN_YEAR = 525600;

    public static function remember(RedirectResponse $response, string $method): RedirectResponse
    {
        return $response->withCookie(cookie(
            self::COOKIE,
            $method,
            self::MINUTES_IN_YEAR,
            '/',
            null,
            request()->isSecure(),
            true,
            false,
            'lax'
        ));
    }
}

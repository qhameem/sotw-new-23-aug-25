<?php

return [
    'daily_limit' => (int) env('LAUNCH_READINESS_DAILY_LIMIT', 20),

    'admin_emails' => array_values(array_filter(array_map(
        static fn (string $email): string => strtolower(trim($email)),
        explode(',', (string) env('LAUNCH_READINESS_ADMIN_EMAILS', 'qhameemb@gmail.com'))
    ))),
];

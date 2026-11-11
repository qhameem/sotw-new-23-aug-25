<?php

return [
    'binaries' => [
        'mysqldump' => env('MYSQLDUMP_PATH', 'mysqldump'),
        'pg_dump' => env('PGDUMP_PATH', 'pg_dump'),
        'sqlite3' => env('SQLITE3_PATH', 'sqlite3'),
    ],
];

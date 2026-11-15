Run this queue on a single worker host with exactly two worker processes. Each
worker handles one scan at a time. Keep the default workers off this queue.
Use a persistent Laravel queue connection (such as database), configure the
renderer, and set the connection's retry_after above the 75-second job timeout.

Example Supervisor program (adapt PHP path, application path and service user):

    [program:readiness-render]
    command=/usr/bin/php /path/to/app/artisan queue:work database --queue=readiness-render --timeout=75
    user=www-data
    process_name=%(program_name)s_%(process_num)02d
    numprocs=2
    autostart=true
    autorestart=true
    stopwaitsecs=90

Deploy the rebuilt frontend assets alongside the PHP code. With a sync queue
connection, scans use the existing synchronous path.

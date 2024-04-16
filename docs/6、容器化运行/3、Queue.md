# Queue

## docker/laravel-queue.conf

```conf
[program:laravel-queue]
command=php artisan queue:work
autostart=true
autorestart=true
priority=10
stdout_events_enabled=true
stderr_events_enabled=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
stopsignal=QUIT
```

## 日志的部分，后续再补充